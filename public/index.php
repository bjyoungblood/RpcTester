<?php

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Setup autoloading
include 'init_autoloader.php';

if (isset($_GET['server'])) {
    $server = $_GET['server'];
    $xml = ($_GET['type'] == 'xml') ? ' selected' : '';
    $json = ($_GET['type'] == 'json') ? ' selected' : '';
    echo '<form method="get">Server: <input type="text" name="server" value="'.$server.'" /><br />Type:<select name="type"><option value="xml"'.$xml.'>XML-RPC</option><option value="json"'.$json.'>JSON-RPC</option></select><br /><input type="submit" /></form>';
} else {
    echo '<form method="get">Server: <input type="text" name="server" /><br />Type:<select name="type"><option value="xml">XML-RPC</option><option value="json">JSON-RPC</option></select><br /><input type="submit" /></form>';
    die();
}

if ($_GET['type'] == 'xml') {
    $client = new Zend\XmlRpc\Client($_GET['server']);
} else {
    $client = new Zend\Json\Server\Client($_GET['server']);
}

if (isset($_GET['method'])) {
    try {
        $params = (isset($_GET['params'])) ? $_GET['params'] : array();

        if ($_GET['type'] == 'xml') {
            $introspector = $client->getIntrospector();
            $signature = $introspector->getMethodSignature($_GET['method']);
            if (isset($signature[0]['parameters'])) {
                foreach ($signature[0]['parameters'] as $index => $type) {
                    if ($type == 'int') {
                        $params[$index] = intval($params[$index]);
                    }
                }
            }
        }

        $response = $client->call($_GET['method'], $params);
        var_dump($response);
    } catch (Zend\XmlRpc\Client\Exception\FaultException $e) {
        echo "<h3>Server Fault</h3>";
        echo $e->getMessage();
        echo $client->getHttpClient()->getResponse();
    } catch (Zend\Json\Server\Exception\ExceptionInterface $e) {
        echo "<h3>Server Fault</h3>";
        echo $e->getMessage();
    } catch (Exception $e) {
        echo "<h3>Request Error</h3>";
        echo $client->getHttpClient()->getResponse();
        echo $e->getMessage();
    }
    echo "<hr />";
}

if ($_GET['type'] == 'xml') {
    $introspector = $client->getIntrospector();

    foreach ($introspector->listMethods() as $method) {
        //if (strpos($method, 'system.') !== false) {
        //    continue;
        //}

        $signature = $introspector->getMethodSignature($method);
        echo "<strong>$method</strong>";
        echo '<form method="get">';
        echo '<input type="hidden" name="server" value="' . $_GET['server'] . '" />';
        echo '<input type="hidden" name="type" value="' . $_GET['type'] . '" />';
        echo '<input type="hidden" name="method" value="' . $method . '" />';

        if (isset($signature[0]['parameters'])) {
            foreach ($signature[0]['parameters'] as $i) {
                echo "Param ($i): <input type=\"text\" name=\"params[]\" /><br />";
            }
        }

        echo '<input type="submit" value="Call Method" />';
        echo '</form><hr />';
    }
} else {
    $smd = json_decode(file_get_contents($_GET['server']), true);

    foreach ($smd['methods'] as $name => $method) {
        $parameters = $method['parameters'];
        echo "<strong>$name</strong>";
        echo '<form method="get">';
        echo '<input type="hidden" name="server" value="' . $_GET['server'] . '" />';
        echo '<input type="hidden" name="type" value="' . $_GET['type'] . '" />';
        echo '<input type="hidden" name="method" value="' . $name . '" />';

        if (isset($method['parameters'])) {
            foreach ($method['parameters'] as $i) {
                echo "Param ({$i['name']}): <input type=\"text\" name=\"params[{$i['name']}]\" /><br />";
            }
        }

        echo '<input type="submit" value="Call Method" />';
        echo '</form><hr />';
    }
}
