<?php
 /**
  * @author Tobias Hinz <hallo@tobiashinz.de>
  * @version 0.0.1
  */
class DayOneEntry {
   /**
     * @var string $_uuid           Saves the UUID of the entry
     * @var string $_creationDate   Saves the creation data of the entry
     * @var string $_activity       Saves the activity type
     * @var string $_creator        Saves the creator
     * @var string $_entryText      Saves the Text of the entry
     * @var string $_timeZone       Saves the TimeZone where entry was created
     * @var mixed $_music           Saves information about music
     * @var mixed $_location        Saves information about location
     * @var bool $_debug            Saves if Debug-mode is used
     */
    protected $_uuid, $_creationDate, $_activity, $_creator, $_entryText, $_timeZone, $_music, $_location, $_debug;

    const VERSION = '0.0.1';

    /**
     * If UUID is submitted then use that one, otherwise generate uuid
     *
     * @param bool $debug (optional) Use debug-mode
     * @param string $uuid (optional) Use specific 32character UUID for new entry
     */
    public function __construct($debug = false, $uuid = null) {
        if ($uuid === null) {
            $this->_uuid = $this->generateUuid();
        } else {
            if (strlen($uuid) !== 32) {
                throw new Exception("UUID has to be 32 characters long", 1);
            }
            $this->_uuid = $uuid;
        }

        // Set general stuff
        $this->setTime(time());
        $this->_activity = 'Stationary';
        $this->_timeZone = date_default_timezone_get();
        $this->_creator = array(
            'Device Agent' => 'DayOne PHP',
            'Generation Date' => strval(date('Y-m-d\TG:i:s\Z')),
            'Host Name' => 'DayOne PHP',
            'OS Agent' => 'DayOne PHP',
            'Software Agent' => 'DayOne PHP' . ' ' . self::VERSION
        );
        $this->entryText = '';
        $this->debug = $debug;

        if ($this->debug) {
            echo 'Entry created successfully';
        }

    }
    /**
     * Sets the text for the DayOne-Entry
     *
     * @param string $text Text that should be added to entry
     */
    public function setEntryText($text) {
        // replace newline with PHP_EOL
        $text = str_replace(
            array('\r\n', '\r', '\n', '<br>'),      // search for these
            PHP_EOL,                                // replace with real EOL
            $text);

        // replace characters that could lead to problems
        $text = htmlspecialchars($text);

        $this->entryText = $text;

        if ($this->debug) {
            echo 'EntryText addedd successfully';
        }
    }

    /**
     * Sets time of entry
     *
     * @param int $time Timestampt
     */
    public function setTime($time) {
        $this->_creationDate = strval(date('Y-m-d\TG:i:s\Z', $time));
    }

    /**
     * Sets location for DayOne-Entry
     *
     * @param string $city City's name
     * @param string $country Country
     * @param string $locality Locality (e.g. district)
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param string $name Name of Location
     */
    public function setLocation($city, $country, $locality, $latitude, $longitude, $name) {
        if (empty($city) || empty($country) || empty($locality) || empty($latitude) || empty($longitude) || empty($name)) {
            die('setLocation(): all fields must be set');
        }
        $this->_location = array(
            'city' => strval($city),
            'country' => strval($country),
            'locality' => strval($locality),
            'latitude' => floatval($latitude),
            'longitude' => floatval($longitude),
            'name' => strval($name),
        );

        if ($this->debug) {
            echo 'Location set successfully';
        }
    }

    /**
     * Returns UUID of DayOne-Entry
     *
     * @return string The UUID
     */
    public function tellUuid() {
        return $this->_uuid;
    }

    /**
     * Generates the text for the DayOne-Entry file
     */
    protected function generateFileText() {
        // Template-Body laden
        $template = file_get_contents(dirname(__FILE__) . '/templates/body.template');

        $template = str_replace('{{Creation_Date}}', $this->_creationDate, $template);
        $template = str_replace('{{Creator_Generation-Date}}', $this->_creator['Generation Date'], $template);
        $template = str_replace('{{Creator_Device-Agent}}', $this->_creator['Device Agent'], $template);
        $template = str_replace('{{Creator_Host-Name}}', $this->_creator['Host Name'], $template);
        $template = str_replace('{{Creator_OS-Agent}}', $this->_creator['OS Agent'], $template);
        $template = str_replace('{{Creator_Software-Agent}}', $this->_creator['Software Agent'], $template);
        $template = str_replace('{{UUID}}', $this->_uuid, $template);
        $template = str_replace('{{Time-Zone}}', $this->_timeZone, $template);
        $template = str_replace('{{Activity}}', $this->_activity, $template);
        $template = str_replace('{{Entry-Text}}', $this->entryText, $template);

        // Check if location has to be added
        if (isset($this->_location)) {
            // Load Template-Location
            $templateLocation = file_get_contents(dirname(__FILE__) . '/templates/location.template');

            $templateLocation = str_replace('{{City}}', $this->_location['city'], $templateLocation);
            $templateLocation = str_replace('{{Country}}', $this->_location['country'], $templateLocation);
            $templateLocation = str_replace('{{Latitude}}', $this->_location['latitude'], $templateLocation);
            $templateLocation = str_replace('{{Longitude}}', $this->_location['longitude'], $templateLocation);
            $templateLocation = str_replace('{{Locality}}', $this->_location['locality'], $templateLocation);
            $templateLocation = str_replace('{{Place_Name}}', $this->_location['name'], $templateLocation);

            // Put location text into DayOne-Entry
            $template = str_replace('{{Location}}', $templateLocation, $template);
        } else {
            $template = str_replace('{{Location}}', '', $template);
        }

        return $template;
    }

    /**
     * Saves the DayOne-Entry file
     *
     * @param string $saveDir (optional) Set the directory where entry is saved
     */
    public function save($saveDir = null) {
        // check if special directory for entries is set
        if ($saveDir === null) {
            $saveDir = getcwd() . '/entries/';
        }

        // create folder for entries in working directory
        if (!file_exists($saveDir)) {
            if (!mkdir($saveDir)) {
                die('Was not able to create folder for entries!');
                throw new Exception("Was not able to create folder for entries", 1);
            }
        }

        // generate text to put into entry
        $file = $this->generateFileText();

        // put text into file
        file_put_contents($saveDir . $this->_uuid . '.doentry', $file);

        // echo message of debugging is activated
        if ($this->debug) {
            echo 'Entry saved successfully';
        }
    }

    /**
     * Generates a 32 character UUID
     *
     * @return string Generated UUID
     */
    protected function generateUuid() {
        return strtoupper(sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        ));
    }

}
?>
