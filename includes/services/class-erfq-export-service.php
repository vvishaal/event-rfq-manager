<?php
/**
 * Export Service
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Export_Service
 *
 * Handles CSV and PDF export of entries
 */
class ERFQ_Export_Service {

    /**
     * Export entries to CSV
     *
     * @param array $entry_ids Entry IDs to export
     * @param int   $form_id   Form ID (for field definitions)
     *
     * @return string CSV content
     */
    public function export_csv($entry_ids, $form_id = null) {
        $entries = array();
        $form = null;
        $fields = array();

        // Get entries
        foreach ($entry_ids as $entry_id) {
            $entry = ERFQ_Entry::get_by_id($entry_id);
            if ($entry) {
                $entries[] = $entry;
                if (!$form_id) {
                    $form_id = $entry->get_form_id();
                }
            }
        }

        if (empty($entries)) {
            return '';
        }

        // Get form and fields
        if ($form_id) {
            $form = ERFQ_Form::get_by_id($form_id);
            if ($form) {
                $fields = $form->get_fields();
            }
        }

        // Build CSV
        $output = fopen('php://temp', 'r+');

        // Header row
        $headers = array('Entry ID', 'Submitted Date', 'Status', 'IP Address');

        foreach ($fields as $field) {
            $headers[] = isset($field['label']) ? $field['label'] : $field['id'];
        }

        fputcsv($output, $headers);

        // Data rows
        $registry = ERFQ_Field_Registry::get_instance();

        foreach ($entries as $entry) {
            $row = array(
                $entry->get_id(),
                $entry->get_submitted_at(),
                $entry->get_status_label(),
                $entry->get_ip_address(),
            );

            $data = $entry->get_data();

            foreach ($fields as $field) {
                $field_id = isset($field['id']) ? $field['id'] : '';
                $value = isset($data[$field_id]) ? $data[$field_id] : '';

                $field_type = $registry->get($field['type'] ?? 'text');
                if ($field_type) {
                    $export_value = $field_type->get_export_value($field, $value);
                } else {
                    $export_value = is_array($value) ? implode(', ', $value) : $value;
                }

                $row[] = $export_value;
            }

            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate CSV download response
     *
     * @param array  $entry_ids Entry IDs
     * @param int    $form_id   Form ID
     * @param string $filename  Download filename
     */
    public function download_csv($entry_ids, $form_id = null, $filename = null) {
        $csv = $this->export_csv($entry_ids, $form_id);

        if (empty($csv)) {
            wp_die(__('No entries to export.', 'event-rfq-manager'));
        }

        if (!$filename) {
            $filename = 'erfq-export-' . date('Y-m-d-His') . '.csv';
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
    }

    /**
     * Export a single entry to PDF
     *
     * @param int $entry_id Entry ID
     *
     * @return string|false PDF content or false on failure
     */
    public function export_pdf($entry_id) {
        $entry = ERFQ_Entry::get_by_id($entry_id);
        if (!$entry) {
            return false;
        }

        $form = $entry->get_form();
        if (!$form) {
            return false;
        }

        // Check if TCPDF is available
        $tcpdf_path = ERFQ_PLUGIN_DIR . 'vendor/tcpdf/tcpdf.php';
        if (!file_exists($tcpdf_path)) {
            // Try to use simple HTML to PDF
            return $this->generate_simple_pdf($entry, $form);
        }

        require_once $tcpdf_path;

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Event RFQ Manager');
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle('Entry #' . $entry->get_id());

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Add a page
        $pdf->AddPage();

        // Build HTML content
        $html = $this->build_pdf_content($entry, $form);

        // Write HTML
        $pdf->writeHTML($html, true, false, true, false, '');

        // Return PDF content
        return $pdf->Output('', 'S');
    }

    /**
     * Generate PDF download response
     *
     * @param int    $entry_id Entry ID
     * @param string $filename Download filename
     */
    public function download_pdf($entry_id, $filename = null) {
        $pdf = $this->export_pdf($entry_id);

        if (!$pdf) {
            wp_die(__('Failed to generate PDF.', 'event-rfq-manager'));
        }

        if (!$filename) {
            $filename = 'entry-' . $entry_id . '-' . date('Y-m-d') . '.pdf';
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: no-cache, no-store, must-revalidate');

        echo $pdf;
        exit;
    }

    /**
     * Build PDF HTML content
     *
     * @param ERFQ_Entry $entry Entry object
     * @param ERFQ_Form  $form  Form object
     *
     * @return string HTML content
     */
    protected function build_pdf_content($entry, $form) {
        $registry = ERFQ_Field_Registry::get_instance();
        $fields = $form->get_fields();
        $data = $entry->get_data();

        $html = '<style>';
        $html .= 'h1 { color: #333; font-size: 24px; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
        $html .= 'th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }';
        $html .= 'th { background-color: #f5f5f5; font-weight: bold; width: 35%; }';
        $html .= '.meta { color: #666; font-size: 12px; margin-bottom: 20px; }';
        $html .= '</style>';

        $html .= '<h1>' . esc_html($form->get_title()) . '</h1>';

        $html .= '<div class="meta">';
        $html .= '<strong>' . __('Entry ID:', 'event-rfq-manager') . '</strong> ' . $entry->get_id() . '<br>';
        $html .= '<strong>' . __('Submitted:', 'event-rfq-manager') . '</strong> ' . $entry->get_submitted_at() . '<br>';
        $html .= '<strong>' . __('Status:', 'event-rfq-manager') . '</strong> ' . $entry->get_status_label() . '<br>';
        $html .= '</div>';

        $html .= '<table>';

        foreach ($fields as $field) {
            $field_id = isset($field['id']) ? $field['id'] : '';
            $label = isset($field['label']) ? $field['label'] : $field_id;

            if (!isset($data[$field_id])) {
                continue;
            }

            $value = $data[$field_id];
            $display_value = $registry->get_display_value($field, $value);

            if (empty($display_value) && $display_value !== '0') {
                continue;
            }

            // Strip HTML for PDF
            $display_value = strip_tags($display_value, '<br>');
            $display_value = str_replace('<br>', "\n", $display_value);

            $html .= '<tr>';
            $html .= '<th>' . esc_html($label) . '</th>';
            $html .= '<td>' . nl2br(esc_html($display_value)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Generate a simple PDF without TCPDF library
     * Uses browser print functionality
     *
     * @param ERFQ_Entry $entry Entry object
     * @param ERFQ_Form  $form  Form object
     *
     * @return string HTML for print
     */
    protected function generate_simple_pdf($entry, $form) {
        // Return HTML that can be printed as PDF
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<title>Entry #' . $entry->get_id() . '</title>';
        $html .= '<style>@media print { body { margin: 0; } }</style>';
        $html .= '</head><body>';
        $html .= $this->build_pdf_content($entry, $form);
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Export form definition to JSON
     *
     * @param int $form_id Form ID
     *
     * @return string JSON content
     */
    public function export_form_json($form_id) {
        $form = ERFQ_Form::get_by_id($form_id);
        if (!$form) {
            return '';
        }

        $export_data = array(
            'version'  => ERFQ_VERSION,
            'exported' => current_time('mysql'),
            'form'     => $form->to_array(),
        );

        return wp_json_encode($export_data, JSON_PRETTY_PRINT);
    }

    /**
     * Import form from JSON
     *
     * @param string $json JSON content
     *
     * @return int|WP_Error Form ID or error
     */
    public function import_form_json($json) {
        $data = json_decode($json, true);

        if (!$data || !isset($data['form'])) {
            return new WP_Error('invalid_json', __('Invalid form data.', 'event-rfq-manager'));
        }

        $form_data = $data['form'];

        // Remove ID to create new form
        unset($form_data['id']);

        // Add "(Imported)" to title
        if (isset($form_data['title'])) {
            $form_data['title'] .= ' ' . __('(Imported)', 'event-rfq-manager');
        }

        $form = ERFQ_Form::from_array($form_data);
        $form->set_status('draft');

        return $form->save();
    }
}
