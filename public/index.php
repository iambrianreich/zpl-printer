<?php

/**
 * ZPL Printer Emulator.
 *
 * @author  Brian Reich <breich@reich-consulting.net>
 * @copyright  Copyright (C) 2018 Reich Web Consulting
 */
class ZplPrinterEmulator
{
    /**
     * The emulator configuration.
     *
     * @var array
     */
    private $config;

    /**
     * Returns the configured output directory.
     *
     * @return string Returns the configured output directory.
     */
    public function getOutputDirectory() : string
    {
        return trim($this->config['outputPath'], DIRECTORY_SEPARATOR);
    }

    /**
     * Returns the configured filename format.
     *
     * @return string Returns the configured filename format.
     */
    public function getFileTemplate() : string
    {
        return $this->config['fileTemplate'];
    }

    /**
     * Returns the configured date format.
     *
     * @return string Returns the configured date format.
     */
    public function getDateFormat() : string
    {
        return $this->config['dateFormat'];
    }

    /**
     * Returns the full path to the output file.
     *
     * @param  string $extension The file extension.
     * @return string Returns the path to the output file.
     */
    public function getOutputPath(string $extension = 'pdf') : string
    {
        $path         = $this->getOutputDirectory() . DIRECTORY_SEPARATOR;
        $fileTemplate = $this->getFileTemplate();
        $dateFormat   = $this->getDateFormat();
        $base         = str_replace(
            '%timestamp%',
            date($dateFormat),
            $fileTemplate
        );

        return $path . $base . '.' . $extension;
    }

    /**
     * Prints a ZPL string to a PDF file.
     *
     * @param  string $zpl The ZPL string to print to PDF.
     * @return string Returns the path to the PDF file.
     * @throws \RuntimeException if printing fails.
     */
    public function printZpl(string $zpl) : string
    {
        $filename = $this->getOutputPath();
        $curl     = curl_init();

        // adjust print density (8dpmm), label width (4 inches), label height (6 inches), and label index (0) as necessary
        curl_setopt($curl, CURLOPT_URL, "http://api.labelary.com/v1/printers/8dpmm/labels/4x6/0/");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $zpl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept: application/pdf")); // omit this line to get PNG images back
        $result = curl_exec($curl);

        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200) {
            $file = fopen($filename, "w"); // change file name for PNG images
            fwrite($file, $result);
            fclose($file);
        } else {
            throw new \RuntimeException('Failed to get PDF from labelry API.');
        }
        curl_close($curl);
        return $filename;
    }

    /**
     * Gets ZPL data from the HTTP POST request and sends it through Emulator.
     *
     * @return string Returns the path to the generated PDF file.
     * @throws RuntimeException if printing fails.
     */
    public function printPostData() : string
    {
        $zpl = file_get_contents('php://input');

        if (empty($zpl)) {
            throw new \RuntimeException(
                "No ZPL data passted in POST request."
            );
        }

        return $this->printZpl($zpl);
    }

    /**
     * Creates the ZplPrinterEmulator.
     *
     * @throws  RuntimeException if an error occurs while creating the Emulator.
     */
    public function __construct()
    {
        $this->config = $this->configure();
    }

    /**
     * Configured the ZplPrinterEmulator.
     *
     * Attempts to read the config.php file from the project root. If none
     * exists the default configuration is used. If the config file exists it
     * is included and the array it returns is used as the configuration.
     *
     * @return [type] [description]
     */
    private function configure()
    {
        $filename = $this->getConfigFilename();

        if (! file_exists($filename)) {
            return $this->getDefaultConfiguration();
        }

        $config = include($filename);

        if ($config === false) {
            throw new RuntimeException(
                "Could not read configuration file $filename"
            );
        }

        // Merge in defaults.
        return array_merge($this->getDefaultConfiguration(), $config);
    }

    /**
     * Returns the path to the configuration file.
     *
     * @return string Returns the path to the configuration file.
     */
    private function getConfigFilename() : string
    {
        return realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'config.php';
    }

    /**
     * Returns the default configuration.
     *
     * @return array Returns the default configuration.
     */
    private function getDefaultConfiguration() : array
    {
        return [
            'outputPath'    => sys_get_temp_dir(),
            'fileTemplate'  => 'label-%timestamp%',
            'dateFormat'    => 'Y-m-d_H-i-s'
        ];
    }
}

try {
    $emulator = new ZplPrinterEmulator();
    $emulator->printPostData();
    http_response_code(200);
} catch (\Exception $exception) {
    http_response_code(500);
}
