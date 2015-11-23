# Joomla ListPipe / AutoNetTV plugin

This is Wizory's Joomla port of the [ListPipe plugin for WordPress](https://wordpress.org/plugins/listpipe/) which:

 > Pulls Powerful Custom Content from your ListPipe account and inserts it into your posts.

See [listpipe.com](https://listpipe.com/) and [AutoNetTV driveMarketing](http://www.autonettv.com/products/drive-marketing/)for details.

## Requirements
- Joomla 1.6 or later
- An account with a system pushing articles via listpipe (setup as an externally hosted WordPress site)

## Usage
- download and install the plugin
- choose a **category** and **user** on the plugin parameters page
- ensure the plugin is enabled
- wait for content to show up :smile:

## Testing

To test functionality of the plugin directly you can use curl:

```bash
curl -v "http://yoursite.com/index.php?action=GetDraft&DraftKey=1&ApprovalKey=1&BlogPostingID=1&debug=true"
```

You should see something like the following:

```
*   Trying 66.147.240.189...
* Connected to yoursite.com (1.2.3.4) port 80 (#0)
> GET /index.php?action=GetDraft&DraftKey=1&ApprovalKey=1&BlogPostingID=1&debug=true HTTP/1.1
> Host: yoursite.com
> User-Agent: curl/7.43.0
> Accept: */*
>
< HTTP/1.1 200 OK
< Date: Mon, 23 Nov 2015 18:05:52 GMT
< Server: Apache
< Set-Cookie: 8856a049647bfe6acb6412a4ba8698b0=01a0ce7adef5f0876fd28ba4f10b5a00; path=/
< Vary: Accept-Encoding
< Transfer-Encoding: chunked
< Content-Type: text/html; charset=UTF-8
<
* Connection #0 to host yoursite.com left intact
success
```

Also in the log directory of the joomla site a log file should be generated/appended with some details about the "fake" post.

## Troubleshooting

If you don't see the success message, get a non-200 return code, etc. look for any firewall or security extensions and try disabling them (RSFirewall is known to cause issues in some cases/configurations).

The origin IP addresses tend to be static so you can generally whitelist those.

If you can't figure out what's not working feel free to create an issue with as much detail as possible.