<?php

#############################################################################
# CDDB                                 (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# http://www.izzysoft.de/                                                   #
# extended & modified by Ed                                                 #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Discogs;

/**
 * The request class
 * Here we emulate a browser accessing the Discogs site. You don't need to
 * call any of its method directly - they are rather used by the Discogs classes.
 */
class Request
{
    private $ch;
    private $urltoopen;
    private $page;
    private $requestHeaders = array();
    private $responseHeaders = array();
    private $config;

    /**
     * No need to call this.
     * @param string $url URL to open
     * @param Config $config The Config object to use
     */
    public function __construct($url, Config $config)
    {
        $this->config = $config;
        $this->ch = curl_init($url);
        curl_setopt($this->ch, CURLOPT_ENCODING, "");
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array(&$this, "callback_CURLOPT_HEADERFUNCTION"));
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);

        $this->urltoopen = $url;

        $this->addHeaderLine('Referer', 'https://' . $config->discogsSite . '/');
        curl_setopt($this->ch, CURLOPT_USERAGENT, $config->default_agent);

    }

    public function addHeaderLine($name, $value)
    {
        $this->requestHeaders[] = "$name: $value";
    }

    /**
     * Send a request Discogs site
     * @return boolean success
     * @throws Exception\Http
     */
    public function sendRequest()
    {
        $this->responseHeaders = array();
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->requestHeaders);
        $this->page = curl_exec($this->ch);
        curl_close($this->ch);
        if ($this->page !== false) {
            return true;
        }
        return false;
    }

    /**
     * Get the Response body
     * @return string page
     */
    public function getResponseBody()
    {
        return $this->page;
    }

    /**
     * Set the URL we need to parse
     * @param string $url
     */
    public function setURL($url)
    {
        $this->urltoopen = $url;
        curl_setopt($this->ch, CURLOPT_URL, $url);
    }

    /**
     * Get a header value from the response
     * @param string $header header field name
     * @return string header value
     */
    public function getResponseHeader($header)
    {
        $headers = $this->getLastResponseHeaders();
        foreach ($headers as $head) {
            if (is_integer(stripos($head, $header))) {
                $hstart = strpos($head, ": ");
                $head = trim(substr($head, $hstart + 2, 100));
                return $head;
            }
        }
        return '';
    }

    /**
     * HTTP status code of the last response
     * @return int|null null if last request failed
     */
    public function getStatus()
    {
        $headers = $this->getLastResponseHeaders();
        if (empty($headers[0])) {
            return null;
        }

        if (!preg_match("#^HTTP/[\d\.]+ (\d+)#i", $headers[0], $matches)) {
            return null;
        }

        return (int)$matches[1];
    }

    /**
     * Get the URL to redirect to if a 30* was returned
     * @return string|null URL to redirect to if 300, otherwise null
     */
    public function getRedirect()
    {
        $status = $this->getStatus();
        if ($status == 301 || $status == 302 || $status == 303 || $status == 307 || $status == 308) {
            foreach ($this->getLastResponseHeaders() as $header) {
                if (strpos(trim(strtolower($header)), 'location') !== 0) {
                    continue;
                }
                $aline = explode(': ', $header);
                $target = trim($aline[1]);
                $urlParts = parse_url($target);
                if (!isset($urlParts['host'])) {
                    $initialRequestUrlParts = parse_url($this->urltoopen);
                    $target = $initialRequestUrlParts['scheme'] . "://" . $initialRequestUrlParts['host'] . $target;
                }
                return $target;
            }
        }
    }

    public function getLastResponseHeaders()
    {
        return $this->responseHeaders;
    }

    private function callback_CURLOPT_HEADERFUNCTION($ch, $str)
    {
        $len = strlen($str);
        if ($len) {
            $this->responseHeaders[] = $str;
        }
        return $len;
    }
}