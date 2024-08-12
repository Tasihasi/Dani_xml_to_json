<?php
ini_set('memory_limit', '1024M'); // or higher, e.g., '512M'



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
   

    // Remove specific attributes
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


    // Convert XML to JSON
    $json = json_encode($xml);

    // Check if JSON encoding was successful
    if ($json === false) {
        return json_encode(['error' => 'Failed to convert XML to JSON']);
    }

    // Decode JSON to an associative array
    $data = json_decode($json, true); // true converts it to an associative array

    // Ensure that all Unicode sequences are properly decoded
    $decodedJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // Check if JSON re-encoding was successful
    if ($decodedJson === false) {
        return json_encode(['error' => 'Failed to decode Unicode sequences']);
    }

    return $decodedJson;
}

function Combining_2_jsons_by_id(string $json1, string $json2) {
    // Decode JSON strings into associative arrays
    $array1 = json_decode($json1, true);
    echo "Here is the array1: ", json_encode($array1, JSON_PRETTY_PRINT);
    $array2 = json_decode($json2, true);
    echo "Here is the array2: ", json_encode($array2, JSON_PRETTY_PRINT);


    // Check if JSON decoding was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(["error" => "Invalid JSON input"]);
    }

    // Normalize the key to lowercase for 'id'
    $combined = [];

    // Function to get the 'id' from an array item
    function get_normalized_id($item) {
        foreach ($item as $key => $value) {
            if (strtolower($key) === 'id') {
                return $value;
            }
        }
        return null; // Return null if no 'id' found
    }

    // Populate the combined array with data from the first JSON array
    foreach ($array1 as $item) {
        $id = get_normalized_id($item);
        if ($id !== null) {
            $combined[$id] = $item;
        }
    }

    // Merge the second JSON array into the combined array
    foreach ($array2 as $item) {
        $id = get_normalized_id($item);
        if ($id !== null) {
            if (isset($combined[$id])) {
                // If the id exists in both arrays, merge the arrays
                $combined[$id] = array_merge($combined[$id], $item);
            } else {
                // If the id is only in the second array, just add it
                $combined[$id] = $item;
            }
        }
    }

    // Return the combined array as a JSON string
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
