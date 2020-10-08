# Buto-Plugin-UploadFolder
Upload files to a folder.

## Settings file
Basic settings. Path is the folder to upload to. Url is where the page is where capture widget is.
```
path: /theme/[theme]/data
url: /p/upload_file
```

### Role
If no role is set the role webmaster is added.
```
role:
  - webmaster
```

### Success
One should set success param with js script to be run after each upload/delete. If no set an alert is showed up.
```
success: location.reload();
```


### Max size
Set max_size param to limit file size in MB.
```
max_size: 1
```

### Type
Set type param to restrict file types.
```
type:
  - image/jpeg
```

### Name
Set name param to restrict names.
```
name:
  - '*.jpg'
```

### Method before
Call a method to handle settings on the fly. The method below is an example and can be copied to your own plugin.
```
method:
  before:
    -
      plugin: upload/folder
      method: method_before
```
Example method to set request param id as a folder inside.
```
public function method_before($data){
  if(wfRequest::get('id')){
    if(strstr(wfRequest::get('id'), '.') || strstr(wfRequest::get('id'), '/')){
      exit('Some hack prevention...');
    }
    $data->set('data/path', $data->get('data/path').'/'.wfRequest::get('id'));
    $data->set('data/url', $data->get('data/url').'?id='.wfRequest::get('id'));
  }
  return $data;
}
```



## Widgets

One has to use three widget when using this plugin along with a settings file.

### Include
Including js.
```
type: widget
data:
  plugin: upload/folder
  method: include
```

### Folder
Render upload form and table with files.
```
type: widget
data:
  plugin: upload/folder
  method: folder
  data: yml:/theme/_/config/upload_folder_data.yml
```

### Capture
Handle when user upload or delete files. This must be alone on a page with no other content due to json response. The url where it's located must also be in settings file.
```
content:
  -
    type: widget
    data:
      plugin: upload/folder
      method: capture
      data: yml:/theme/_/config/upload_folder_data.yml
```


