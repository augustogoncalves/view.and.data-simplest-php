<?php
// we need a REST Library, found HttpFul
// Download from http://phphttpclient.com/
// You may need to add permissions for MAC OS with Apache
//     sudo chmod -R 755 /library/webserver/documents
//     (assuming default folder)
include 'httpful.phar';

// define some constants for this quick sample
define(CONSUMER_KEY, "your key here");
define(CONSUMER_SECRET, "your secret here");
define(BASE_URL, 'https://developer.api.autodesk.com');

// if the request URL contains the method being requested
// for instance, a call to view.and.data.php/authenticate
// will redirect to the function with the same name
$apiName = explode('/', trim($_SERVER['PATH_INFO'],'/'))[0];
if (!empty($apiName)){
    // get the function by API name
    try{ $apiFunction = new ReflectionFunction($apiName);}
    catch (Exception $e) { echo ('API not found');}
    
    // run the function and 'echo' it's reponse
    if ($apiFunction != null) echo $apiFunction->invoke();
    
    exit(); // no HTML output
}

// now the APIs
function authenticate(){
    // request body (client key & secret)
    $body = sprintf('client_id=%s' . 
                    '&client_secret=%s' . 
                    '&grant_type=client_credentials',
                    CONSUMER_KEY, CONSUMER_SECRET);

    // prepare a POST request following the documentation
    $response = 
        \Httpful\Request::post(
          BASE_URL . '/authentication/v1/authenticate')
        ->addHeader('Content-Type', 'application/x-www-form-urlencoded')
        ->body($body)
        ->send(); // make the request

    if ( $response->code == 200)
        // access the JSON response directly
        return $response->body->access_token; //$response->body->token_type . ' ' . 
    else
        // something went wrong...
        throw new Exception('Cannot authenticate');
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Minimum PHP View and Data Sample</title>
    <link type="text/css" rel="stylesheet" href="https://developer.api.autodesk.com/viewingservice/v1/viewers/style.css" />
</head>
<script src="https://developer.api.autodesk.com/viewingservice/v1/viewers/viewer3D.min.js?v=1.2.23"></script>
<script>
// This is the basic JavaScript sample code available at the documentation
// It's optimized for 3D models and slightly adjusted for this case

// Show the model specified on the URN parameter
function showModel() {
    var options = {
        'document': 'urn:' + document.getElementById('modelURN').value,
        'env': 'AutodeskProduction',
        'getAccessToken': getToken,
        'refreshToken': getToken,
    };
    var viewerElement = document.getElementById('viewer');
    var viewer = new Autodesk.Viewing.Viewer3D(viewerElement, {});
    Autodesk.Viewing.Initializer(
        options,
        function () {
            viewer.initialize();
            loadDocument(viewer, options.document);
        }
    );
}

// Load the document (urn) on the view object
function loadDocument(viewer, documentId) {
    // Find the first 3d geometry and load that.
    Autodesk.Viewing.Document.load(
        documentId,
        function (doc) { // onLoadCallback
            var geometryItems = [];
            geometryItems = Autodesk.Viewing.Document.getSubItemsWithProperties(doc.getRootItem(), {
                'type': 'geometry',
                'role': '3d'
            }, true);
            if (geometryItems.length > 0) {
                viewer.load(doc.getViewablePath(geometryItems[0]));
            }
        },
        function (errorMsg) { // onErrorCallback
            alert("Load Error: " + errorMsg);
        }
    );
}

// This calls are required if the models stays open for a long time and the token expires
function getToken() {
    return makePOSTSyncRequest("view.and.data.php/authenticate");
}

function makePOSTSyncRequest(url) {
    var xmlHttp = null;
    xmlHttp = new XMLHttpRequest();
    xmlHttp.open("POST", url, false);
    xmlHttp.send(null);
    return xmlHttp.responseText;
}
</script>
<body>
    <div>This is a minimum sample in PHP5.
        <br /> First edit this file and enter your consumer key and consumer secret. Request at <a href="http://forge.autodesk.com">Forge portal</a>.
        <br /> To use this sample you need a model URN. Please upload a model at <a href="http://models.autodesk.io">Models.Autodesk.IO</a></div>
    <hr />
    <div>
        Specify a model URN:
        <input type="text" id="modelURN" />
        <input type="button" value="View model" onclick="showModel()">
    </div>
    <hr />
    <div id="viewer">
    </div>
</body>
</html>