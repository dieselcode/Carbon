<?php

require_once '../vendor/autoload.php';;

try {

    /**
     * See the server > max_buffer setting in diesel.upload.ini
     *
     * A larger buffer is needed to upload files.  This uploader
     * does work now.
     */

    $server = new \Carbon\Core\Server('carbon.upload.ini');
    $server->setDebug(true);


    // default route can be set as "/" (that will take a zero index path)
    $server->route('/upload', function ($route) use ($server) {

        $route->on('data', function ($raw_data, $decoded_data, $connection) use ($route, $server) {

            if (!empty($decoded_data)) {
                $file = parseFileData($decoded_data->filedata);

                // set our allowed mime types
                $allowed_types = array('image/png', 'image/gif', 'image/jpeg');
                // set the max size in bytes
                $max_size = 1024 * 500; // allow up to 500kb files.

                if (in_array($file['mimetype'], $allowed_types)) {
                    if ($file['size'] <= $max_size) {
                        // write the uploaded file

                        $write = file_put_contents(
                            $connection->getId() . $decoded_data->filename,
                            $file['filebuffer']
                        );

                        // send the user a confirmation if the file was written
                        if ($write !== false) {
                            $message = array('status' => 'success', 'filename' => $decoded_data->filename);
                        } else {
                            $message = array('status' => 'error', 'message' => 'File could not be written on server');
                        }
                    } else {
                        $message = array('status' => 'error', 'message' => 'File was too big (max size=' . $max_size . ' bytes)');
                    }
                } else {
                    $message = array('status' => 'error', 'message' => 'Filetype was not in the whitelist');
                }

                var_dump($message);

                $connection->send(json_encode($message));
            }

        });

        $route->on('data.binary', function ($raw_data, $connection) use ($route, $server) {
            var_dump($raw_data);
        });

    });

    $server->run();

} catch (\Carbon\Exception\ServerException $e) {
    var_dump($e->getMessage());
}

// split our upload data into its proper parts
/**
 * For the dual-base64 decoding in here, see test_upload.html.  It's the only way
 * we can reliably transfter the image data on a non-binary connection
 */
function parseFileData($data)
{
    $parts = array();
    $data = base64_decode($data);

    list($header, $imageData) = explode(';', $data, 2);
    list(, $parts['mimetype']) = explode(':', $header, 2);
    list($parts['encoding'], $parts['filebuffer']) = explode(',', $imageData);

    $parts['filebuffer'] = base64_decode($parts['filebuffer']);
    $parts['size'] = strlen($parts['filebuffer']);

    return $parts;
}

?>