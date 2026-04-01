<?php
/**
 * Send the download header to the client.
 *
 * @param  string    $fileName
 * @param  string    $fileType
 * @param  string    $content
 * @access public
 * @return void
 */
public function sendDownHeader($fileName, $fileType, $content, $type = 'content')
{
    /* Clean the ob content to make sure no space or utf-8 bom output. */
    $obLevel = ob_get_level();
    for($i = 0; $i < $obLevel; $i++) ob_end_clean();

    /* Set the downloading cookie, thus the export form page can use it to judge whether to close the window or not. */
    setcookie('downloading', 1, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);

    /* Only download upload file that is in zentao. */
    if($type == 'file' and stripos($content, $this->savePath) !== 0) throw EndResponseException::create('');

    /* Append the extension name auto. */
    $extension = $fileType ? '.' . $fileType : '';
    if(strpos($fileName, $extension) === false) $fileName .= $extension;

    /* urlencode the filename for ie. */
    if(strpos($this->server->http_user_agent, 'MSIE') !== false or strpos($this->server->http_user_agent, 'Trident') !== false or strpos($this->server->http_user_agent, 'Edge') !== false) $fileName = urlencode($fileName);

    /* Judge the content type. */
    $mimes = $this->config->file->mimes;
    $contentType = isset($mimes[$fileType]) ? $mimes[$fileType] : $mimes['default'];

    header("Content-type: $contentType");
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    if($type == 'content') throw EndResponseException::create($content);
    if($type == 'file' and file_exists($content))
    {
        if(stripos($content, $this->app->getBasePath()) !== 0) throw EndResponseException::create('');

        set_time_limit(0);
        $chunkSize = 10 * 1024 * 1024;
        $handle    = fopen($content, "r");
        while(!feof($handle)) echo fread($handle, $chunkSize);
        fclose($handle);
        throw EndResponseException::create('');
    }
}
