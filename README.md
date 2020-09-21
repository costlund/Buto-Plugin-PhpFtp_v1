# Buto-Plugin-PhpFtp_v1

Methods for FTP.

## Settings

```
server: _
user: _
password: _
dir: /_folder_on_ftp_server_
web_folder: public_html
```

If there is no folder to access becaus ftp account is stricted to a specific folder one must set dir param to slash.
```
dir: '/'
```


## Usage

```
$data = new PluginWfArray($data);
wfPlugin::includeonce('php/ftp_v1');
$ftp = new PluginPhpFtp_v1();
$ftp->setData($data->get('data/ftp'));
$ftp->dir = $data->get('data/ftp/dir');
$rawlist = $ftp->rawlist();
$rawlist = $ftp->raw_list_top_level_7($rawlist);
$remote_files = $ftp->rawlist_files($rawlist);
wfHelp::textarea_dump($remote_files);
```
