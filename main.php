<?php
ini_set('memory_limit', '1024M'); // or higher, e.g., '512M'

echo "runing";


// Function to recursively echo attributes of XML elements
function echoAttributes2($element) {
    // Echo element name and attributes if any
    foreach ($element->attributes() as $name => $value) {
        echo "Element: " . $element->getName() . " | Attribute: $name = $value\n";
    }

    // Recursively handle child elements
    foreach ($element->children() as $child) {
        echoAttributes2($child);
    }
}

function echoJsonAttributes($data, $indent = '') {
    foreach ($data as $key => $value) {
        if (is_array($value) || is_object($value)) {
            echo $indent . "$key:\n";
            echoJsonAttributes($value, $indent . '  '); // Increase indent for nested elements
        } else {
            echo $indent . "$key: $value\n";
        }
    }
}

// Function to echo keys recursively and ensure each key is only echoed once
function echoJsonKeys($data, &$keysSeen = []) {
    foreach ($data as $key => $value) {
        if (!in_array($key, $keysSeen)) {
            echo "$key\n";
            $keysSeen[] = $key; // Add the key to the list of seen keys
        }
        
        if (is_array($value) || is_object($value)) {
            echoJsonKeys($value, $keysSeen);
        }
    }
}


// Checking if the string is a vlaid json 
function isValidJSON(string $json) {
    // Decode the JSON string
    json_decode($json);

    // Check if there was an error during decoding
    return (json_last_error() === JSON_ERROR_NONE);
}


/**
 * Converts the XML file at the given path to a JSON string.
 *
 * @param string $path The path to the XML file.
 * @return string A JSON-encoded string representing the XML file's content or an error message.
 */
function Convert_xml_to_json(string $path1): string {
    echo "Converting xmls to json";

    // Check if the file exists
    if (!file_exists($path1)) {
        return json_encode(['error' => 'File does not exist']);
    }

    // Read the content of the XML file
    $xmlContent = file_get_contents($path1);
    
    // Check if the file was read successfully
    if ($xmlContent === false) {
        return json_encode(['error' => 'Unable to read the file']);
    }

    // Convert the XML content to a SimpleXMLElement object
    $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);
    
    // Check if XML loading was successful
    if ($xml === false) {
        return json_encode(['error' => 'Invalid XML content']);
    }

    // Remove specific attributes (if needed)
    foreach ($xml->xpath('//InternalStock') as $node) {
        unset($node[0]);
    }
    foreach ($xml->xpath('//ExternalStock') as $node) {
        unset($node[0]);
    }
    foreach ($xml->xpath('//ArrivingStock') as $node) {
        unset($node[0]);
    }
    foreach ($xml->xpath('//WhenWillArrive') as $node) {
        unset($node[0]);
    }

    // Convert XML to an associative array
    $array = json_decode(json_encode($xml), true);

    // Flatten the array
    $flattenedArray = flatten_array($array);

    // Convert the flattened array back to JSON
    $json = json_encode($flattenedArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // Check if JSON encoding was successful
    if ($json === false) {
        return json_encode(['error' => 'Failed to convert flattened array to JSON']);
    }

    return $json;
}

/**
 * Flattens a multidimensional array.
 *
 * @param array $array The array to flatten.
 * @param string $prefix The prefix for array keys.
 * @return array The flattened array.
 */
function flatten_array(array $array, string $prefix = ''): array {
    $result = [];

    foreach ($array as $key => $value) {
        $new_key = $prefix ? $prefix . '_' . $key : $key;

        if (is_array($value)) {
            $result += flatten_array($value, $new_key);
        } else {
            $result[$new_key] = $value;
        }
    }

    return $result;
}

/**
 * Extracts the normalized ID from an array item.
 *
 * @param array $item The array item.
 * @return mixed The ID value or null if not found.
 */
function get_normalized_id(array $item) {
    foreach ($item as $key => $value) {
        if (strtolower($key) === 'id') {
            return $value;
        }
    }
    return null;
}


/**
 * Combines two JSON objects by their IDs.
 *
 * @param string $json1 The first JSON object.
 * @param string $json2 The second JSON object.
 * @return string A JSON-encoded string representing the combined objects or an error message.
 */
function Combining_2_jsons_by_id(string $json1, string $json2): string {
    echo "Combining JSON objects\n";

    // Decode JSON strings into associative arrays
    $array1 = json_decode($json1, true);
    $array2 = json_decode($json2, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(['error' => 'Invalid JSON input']);
    }

    echo "Here is array1: ", json_encode($array1, JSON_PRETTY_PRINT), "\n";
    echo "Here is array2: ", json_encode($array2, JSON_PRETTY_PRINT), "\n";

    // Normalize and combine arrays by ID
    $combined = [];

    foreach ([$array1, $array2] as $array) {
        foreach ($array as $item) {
            $id = get_normalized_id($item);
            if ($id !== null) {
                $combined[$id] = isset($combined[$id])
                    ? array_merge($combined[$id], $item)
                    : $item;
            }
        }
    }

    return json_encode(array_values($combined));
}

// Example usage
$path1 = 'data/stock_20240603.xml';
$path2 = 'data/20240603.xml';

$json1 = Convert_xml_to_json($path1);
$json2 = Convert_xml_to_json($path2);

echo "The Result::";
echo "Result: " ,Combining_2_jsons_by_id($json1, $json2);

//Convert_copy_depo_xml($path1);
//Convert_copy_depo_xml($path2);
?>
