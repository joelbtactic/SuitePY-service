<?php

/**
 * Suite PY is a simple Python client for SuiteCRM API.
 *
 * Copyright (C) 2017-2018 BTACTIC, SCCL
 * Copyright (C) 2017-2018 Marc Sanchez Fauste
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use SuiteCRM\PDF\Exceptions\PDFException;
use SuiteCRM\PDF\PDFWrapper;

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

// Based on 'modules/AOS_PDF_Templates/generatePdf.php'.

abstract class Base64PDFGenerator
{
    public static function get_base64_pdf($template_id, $bean)
    {
        require_once('modules/AOS_PDF_Templates/templateParser.php');
        require_once('modules/AOS_PDF_Templates/sendEmail.php');
        require_once('modules/AOS_PDF_Templates/AOS_PDF_Templates.php');
        require_once('vendor/autoload.php');

        if (empty($bean->id)) {
            throw new Exception('Invalid bean.');
        }

        $variableName = strtolower($bean->module_dir);
        $lineItemsGroups = array();
        $lineItems = array();

        $sql = "SELECT pg.id, pg.product_id, pg.group_id FROM aos_products_quotes pg LEFT JOIN aos_line_item_groups lig ON pg.group_id = lig.id WHERE pg.parent_type = '" . $bean->object_name . "' AND pg.parent_id = '" . $bean->id . "' AND pg.deleted = 0 ORDER BY lig.number ASC, pg.number ASC";
        $res = $bean->db->query($sql);
        while ($row = $bean->db->fetchByAssoc($res)) {
            $lineItemsGroups[$row['group_id']][$row['id']] = $row['product_id'];
            $lineItems[$row['id']] = $row['product_id'];
        }

        $template = BeanFactory::newBean('AOS_PDF_Templates');
        $template->retrieve($template_id);
        if (empty($template->id)) {
            throw new Exception('Error retrieving template.');
        }

        $object_arr = array();
        $object_arr[$bean->module_dir] = $bean->id;

        //backward compatibility
        $object_arr['Accounts'] = $bean->billing_account_id ?? '';
        $object_arr['Contacts'] = $bean->billing_contact_id ?? '';
        $object_arr['Users'] = $bean->assigned_user_id ?? '';
        $object_arr['Currencies'] = $bean->currency_id ?? '';

        $search = array(
            '/<script[^>]*?>.*?<\/script>/si',      // Strip out javascript
            '/<[\/\!]*?[^<>]*?>/si',        // Strip out HTML tags
            '/([\r\n])[\s]+/',          // Strip out white space
            '/&(quot|#34);/i',          // Replace HTML entities
            '/&(amp|#38);/i',
            '/&(lt|#60);/i',
            '/&(gt|#62);/i',
            '/&(nbsp|#160);/i',
            '/&(iexcl|#161);/i',
            '/<address[^>]*?>/si',
            '/&(apos|#0*39);/',
            '/&#(\d+);/'
        );

        $replace = array(
            '',
            '',
            '\1',
            '"',
            '&',
            '<',
            '>',
            ' ',
            chr(161),
            '<br>',
            "'",
            'chr(%1)'
        );

        $header = preg_replace($search, $replace, (string) $template->pdfheader);
        $footer = preg_replace($search, $replace, (string) $template->pdffooter);
        $text = preg_replace($search, $replace, (string) $template->description);
        $text = str_replace("<p><pagebreak /></p>", "<pagebreak />", $text);
        $text = preg_replace_callback(
            '/\{DATE\s+(.*?)\}/',
            function ($matches) {
                return date($matches[1]);
            },
            $text
        );
        $text = str_replace("\$aos_quotes", "\$" . $variableName, $text);
        $text = str_replace("\$aos_invoices", "\$" . $variableName, $text);
        $text = str_replace("\$total_amt", "\$" . $variableName . "_total_amt", $text);
        $text = str_replace("\$discount_amount", "\$" . $variableName . "_discount_amount", $text);
        $text = str_replace("\$subtotal_amount", "\$" . $variableName . "_subtotal_amount", $text);
        $text = str_replace("\$tax_amount", "\$" . $variableName . "_tax_amount", $text);
        $text = str_replace("\$shipping_amount", "\$" . $variableName . "_shipping_amount", $text);
        $text = str_replace("\$total_amount", "\$" . $variableName . "_total_amount", $text);

        $text = self::populate_group_lines($text, $lineItemsGroups, $lineItems);

        $converted = templateParser::parse_template($text, $object_arr);
        $header = templateParser::parse_template($header, $object_arr);
        $footer = templateParser::parse_template($footer, $object_arr);

        $printable = str_replace("\n", "<br />", (string) $converted);
        ob_clean();
        try {
            $orientation = ($template->orientation == "Landscape") ? "-L" : "";

            // Define custom configuration options
            $mpdfConfig = [
                'mode' => 'utf-8', // Set character encoding (default is utf-8)
                'format' => $template->page_size . $orientation, // Set the page format
                'margin_left' => $template->margin_left,
                'margin_right' => $template->margin_right,
                'margin_top' => $template->margin_top,
                'margin_bottom' => $template->margin_bottom,
                'margin_header' => $template->margin_header,
                'margin_footer' => $template->margin_footer,
                'default_font_size' => 0, // Default font size
                'default_font' => 'DejaVuSansCondensed', // Default font family
                // Add more configuration options as needed
            ];

            // Create a new instance of mPDF with custom configuration
            $pdf = new \Mpdf\Mpdf($mpdfConfig);
            $pdf->autoLangToFont = true;
            $pdf->SetHeader($header);
            $pdf->SetFooter($footer);
            ini_set('pcre.backtrack_limit', '1000000'); // Set an appropriate value based on your needs
            $pdf->WriteHTML($printable);
            return base64_encode($pdf->Output('ignored', 'S'));
        } catch (MpdfException $e) {
            return false;
        }
    }

    private static function populate_group_lines(
        $text,
        $lineItemsGroups,
        $lineItems,
        $element = 'table'
    ) {
        $firstValue = '';
        $firstNum = 0;

        $lastValue = '';
        $lastNum = 0;

        $startElement = '<' . $element;
        $endElement = '</' . $element . '>';


        $groups = BeanFactory::newBean('AOS_Line_Item_Groups');
        foreach ($groups->field_defs as $name => $arr) {
            if (!((isset($arr['dbType']) && strtolower($arr['dbType']) == 'id') || $arr['type'] == 'id' || $arr['type'] == 'link')) {
                $curNum = strpos((string) $text, '$aos_line_item_groups_' . $name);
                if ($curNum) {
                    if ($curNum < $firstNum || $firstNum == 0) {
                        $firstValue = '$aos_line_item_groups_' . $name;
                        $firstNum = $curNum;
                    }
                    if ($curNum > $lastNum) {
                        $lastValue = '$aos_line_item_groups_' . $name;
                        $lastNum = $curNum;
                    }
                }
            }
        }
        if ($firstValue !== '' && $lastValue !== '') {
            //Converting Text
            $parts = explode($firstValue, $text);
            $text = $parts[0];
            $parts = explode($lastValue, $parts[1]);
            if ($lastValue == $firstValue) {
                $groupPart = $firstValue . $parts[0];
            } else {
                $groupPart = $firstValue . $parts[0] . $lastValue;
            }

            if ((is_countable($lineItemsGroups) ? count($lineItemsGroups) : 0) != 0) {
                //Read line start <tr> value
                $tcount = strrpos($text, $startElement);
                $lsValue = substr($text, $tcount);
                $tcount = strpos($lsValue, ">") + 1;
                $lsValue = substr($lsValue, 0, $tcount);


                //Read line end values
                $tcount = strpos($parts[1], $endElement) + strlen($endElement);
                $leValue = substr($parts[1], 0, $tcount);

                //Converting Line Items
                $obb = array();

                $tdTemp = explode($lsValue, $text);

                $groupPart = $lsValue . $tdTemp[(is_countable($tdTemp) ? count($tdTemp) : 0) - 1] . $groupPart . $leValue;

                $text = $tdTemp[0];

                foreach ($lineItemsGroups as $group_id => $lineItemsArray) {
                    $groupPartTemp = self::populate_product_lines($groupPart, $lineItemsArray);
                    $groupPartTemp = self::populate_service_lines($groupPartTemp, $lineItemsArray);

                    $obb['AOS_Line_Item_Groups'] = $group_id;
                    $text .= templateParser::parse_template($groupPartTemp, $obb);
                    $text .= '<br />';
                }
                $tcount = strpos($parts[1], $endElement) + strlen($endElement);
                $parts[1] = substr($parts[1], $tcount);
            } else {
                $tcount = strrpos($text, $startElement);
                $text = substr($text, 0, $tcount);

                $tcount = strpos($parts[1], $endElement) + strlen($endElement);
                $parts[1] = substr($parts[1], $tcount);
            }

            $text .= $parts[1];
        } else {
            $text = self::populate_product_lines($text, $lineItems);
            $text = self::populate_service_lines($text, $lineItems);
        }


        return $text;
    }

    private static function populate_product_lines($text, $lineItems, $element = 'tr')
    {
        $firstValue = '';
        $firstNum = 0;

        $lastValue = '';
        $lastNum = 0;

        $startElement = '<' . $element;
        $endElement = '</' . $element . '>';

        //Find first and last valid line values
        $product_quote = BeanFactory::newBean('AOS_Products_Quotes');
        foreach ($product_quote->field_defs as $name => $arr) {
            if (!((isset($arr['dbType']) && strtolower($arr['dbType']) == 'id')
                || $arr['type'] == 'id' || $arr['type'] == 'link')) {
                $curNum = strpos((string) $text, '$aos_products_quotes_' . $name);

                if ($curNum) {
                    if ($curNum < $firstNum || $firstNum == 0) {
                        $firstValue = '$aos_products_quotes_' . $name;
                        $firstNum = $curNum;
                    }
                    if ($curNum > $lastNum) {
                        $lastValue = '$aos_products_quotes_' . $name;
                        $lastNum = $curNum;
                    }
                }
            }
        }

        $product = BeanFactory::newBean('AOS_Products');
        foreach ($product->field_defs as $name => $arr) {
            if (!((isset($arr['dbType']) && strtolower($arr['dbType']) == 'id')
                || $arr['type'] == 'id' || $arr['type'] == 'link')) {
                $curNum = strpos((string) $text, '$aos_products_' . $name);
                if ($curNum) {
                    if ($curNum < $firstNum || $firstNum == 0) {
                        $firstValue = '$aos_products_' . $name;


                        $firstNum = $curNum;
                    }
                    if ($curNum > $lastNum) {
                        $lastValue = '$aos_products_' . $name;
                        $lastNum = $curNum;
                    }
                }
            }
        }

        if ($firstValue !== '' && $lastValue !== '') {

            //Converting Text
            $tparts = explode($firstValue, $text);
            $temp = $tparts[0];

            //check if there is only one line item
            if ($firstNum == $lastNum) {
                $linePart = $firstValue;
            } else {
                $tparts = explode($lastValue, $tparts[1]);
                $linePart = $firstValue . $tparts[0] . $lastValue;
            }


            $tcount = strrpos($temp, $startElement);
            $lsValue = substr($temp, $tcount);
            $tcount = strpos($lsValue, ">") + 1;
            $lsValue = substr($lsValue, 0, $tcount);

            //Read line end values
            $tcount = strpos($tparts[1], $endElement) + strlen($endElement);
            $leValue = substr($tparts[1], 0, $tcount);
            $tdTemp = explode($lsValue, $temp);

            $linePart = $lsValue . $tdTemp[(is_countable($tdTemp) ? count($tdTemp) : 0) - 1] . $linePart . $leValue;
            $parts = explode($linePart, $text);
            $text = $parts[0];

            //Converting Line Items
            if ((is_countable($lineItems) ? count($lineItems) : 0) != 0) {
                foreach ($lineItems as $id => $productId) {
                    if ($productId != null && $productId != '0') {
                        $obb['AOS_Products_Quotes'] = $id;
                        $obb['AOS_Products'] = $productId;
                        $text .= templateParser::parse_template($linePart, $obb);
                    }
                }
            }

            $partsCount = is_countable($parts) ? count($parts) : 0;

            for ($i = 1; $i < $partsCount; $i++) {
                $text .= $parts[$i];
            }
        }
        return $text;
    }

    private static function populate_service_lines($text, $lineItems, $element = 'tr')
    {
        $firstValue = '';
        $firstNum = 0;

        $lastValue = '';
        $lastNum = 0;

        $startElement = '<' . $element;
        $endElement = '</' . $element . '>';

        $text = str_replace("\$aos_services_quotes_service", "\$aos_services_quotes_product", (string) $text);

        //Find first and last valid line values
        $product_quote = BeanFactory::newBean('AOS_Products_Quotes');
        foreach ($product_quote->field_defs as $name => $arr) {
            if (!((isset($arr['dbType']) && strtolower($arr['dbType']) == 'id')
                || $arr['type'] == 'id' || $arr['type'] == 'link')) {
                $curNum = strpos($text, '$aos_services_quotes_' . $name);
                if ($curNum) {
                    if ($curNum < $firstNum || $firstNum == 0) {
                        $firstValue = '$aos_products_quotes_' . $name;
                        $firstNum = $curNum;
                    }
                    if ($curNum > $lastNum) {
                        $lastValue = '$aos_products_quotes_' . $name;
                        $lastNum = $curNum;
                    }
                }
            }
        }
        if ($firstValue !== '' && $lastValue !== '') {
            $text = str_replace("\$aos_products", "\$aos_null", $text);
            $text = str_replace("\$aos_services", "\$aos_products", $text);

            //Converting Text
            $tparts = explode($firstValue, $text);
            $temp = $tparts[0];

            //check if there is only one line item
            if ($firstNum == $lastNum) {
                $linePart = $firstValue;
            } else {
                $tparts = explode($lastValue, $tparts[1]);
                $linePart = $firstValue . $tparts[0] . $lastValue;
            }

            $tcount = strrpos($temp, $startElement);
            $lsValue = substr($temp, $tcount);
            $tcount = strpos($lsValue, ">") + 1;
            $lsValue = substr($lsValue, 0, $tcount);

            //Read line end values
            $tcount = strpos($tparts[1], $endElement) + strlen($endElement);
            $leValue = substr($tparts[1], 0, $tcount);
            $tdTemp = explode($lsValue, $temp);

            $linePart = $lsValue . $tdTemp[(is_countable($tdTemp) ? count($tdTemp) : 0) - 1] . $linePart . $leValue;
            $parts = explode($linePart, $text);
            $text = $parts[0];

            //Converting Line Items
            if ((is_countable($lineItems) ? count($lineItems) : 0) != 0) {
                foreach ($lineItems as $id => $productId) {
                    if ($productId == null || $productId == '0') {
                        $obb['AOS_Products_Quotes'] = $id;
                        $text .= templateParser::parse_template($linePart, $obb);
                    }
                }
            }

            $partsCount = is_countable($parts) ? count($parts) : 0;

            for ($i = 1; $i < $partsCount; $i++) {
                $text .= $parts[$i];
            }
        }
        return $text;
    }
}
