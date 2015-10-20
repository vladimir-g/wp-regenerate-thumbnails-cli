<?php
namespace WpRegenerateThumbnailsCli;

class Regenerator
{
    public $width = 40;

    public function __construct($wpRoot, $silent=true)
    {
        $this->root = $wpRoot;
        $this->silent = $silent;
        // Load Wordpress
        $this->msg('Loading Wordpress');
        define('BASE_PATH', $this->root);
        define('WP_USE_THEMES', false);
        // Require main wp loader
        require_once($this->makePath($this->root, 'wp-load.php'));
        // Require attachment functions
        require_once($this->makePath(
            $this->root,
            'wp-admin',
            'includes',
            'image.php'
        ));
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    // Print message
    protected function msg($text)
    {
        if (!$this->silent)
            echo $text."\n";
    }

    // Create path from array parts
    protected function makePath()
    {
        $processed = array();
        foreach (func_get_args() as $p) {
            if (empty($p))
                continue;
            if (empty($processed)) {
                $processed[] = rtrim($p, DIRECTORY_SEPARATOR);
            } else {
                $processed[] = trim($p, DIRECTORY_SEPARATOR);
            }
        }
        return implode(DIRECTORY_SEPARATOR, $processed);
    }

    // Start regeneration
    public function run()
    {
        $sql = "SELECT ID FROM ".$this->wpdb->posts." ".
            "WHERE post_type = 'attachment' ".
            "AND post_mime_type LIKE 'image/%' ORDER BY ID";
        $images = $this->wpdb->get_results($sql);
        $overall = count($images);
        $counter = 0;
        $errors = array();
        foreach ($images as $image) {
            // Get attachment path
            $path = get_attached_file($image->ID);
            $skip = false;
            $error = null;
            if ($path === false || !file_exists($path)) {
                $error = 'Error: file does not exists for ID '.$image->ID;
                $skip = true;
            }
            // Generate new metadata
            $metadata = \wp_generate_attachment_metadata($image->ID, $path);
            if (!$skip && empty($metadata)) {
                $error = 'Error: metadata error for ID '.$image->ID;
                $skip = true;
            }
            if (!$skip && is_wp_error($metadata)) {
                $error = 'Error: metadata error for ID '.
                    $image->ID.': "'.
                    $metadata->get_error_message().'"';
                $skip = true;
            }
            // Update metadata
            if (!$skip) {
                \wp_update_attachment_metadata($image->ID, $metadata);
            }
            // Show progress
            if ($error !== null) {
                $errors[] = $error;
                $this->msg("\n".$error);
            }
            $count++;
            $this->printProgress($count, $overall);
        }
        $this->msg('');         // End
        return $errors;
    }

    protected function printProgress($count, $overall) {
        if ($this->silent)
            return;
        $percent = $count / $overall;
        echo "\r[";
        for ($i = 0; $i < round($this->width * $percent); $i++) {
            echo '#';
        }
        for ($i = round($this->width * $percent); $i < $this->width; $i++) {
            echo '-';
        }
        echo '] '.(round($percent * 100))."%";
    }
}


// Usage text
const HELP = <<<EOD
Usage:
php /path/to/wp-regenerate-thumbnails-cli.php -p|--path /path/to/wp/root/
    -p|--path -- path to Wordpress root directory (required)
    -h|--help -- print this message and exit

EOD;

// Print text to terminal
function printc($text) {
    if (!SILENT)
        echo $text."\n";
}

// Command line entry point
function main() {
    $options = getopt("hsp:", array(
        'help',
        'silent',
        'path:',
    ));

    // Check if help requested
    if (!empty(array_intersect(array('h', 'help'),
                               array_keys($options)))) {
        printc(HELP);
        die();
    }

    if (empty(array_intersect(array('p', 'path'),
                              array_keys($options)))) {
        printc("Error: path required\n");
        printc(HELP);
        die();
    }

    define('SILENT', (!empty(array_intersect(array('s', 'silent'),
                                             array_keys($options)))));
    // Process path
    $path = array_key_exists('p', $options) ? $options['p'] : $options['path'];
    printc('Using path: '.$path);
    $path = realpath($path);
    if ($path === NULL || !is_dir($path)) {
        printc('Error: directory does not exists');
        die();
    }
    // Add directory separator to path
    $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

    $regenerator = new Regenerator($path, SILENT);
    $regenerator->run();
}

// Run main if used from CLI
if (!count(debug_backtrace())) {
    main();
}
