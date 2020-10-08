<?php
class PluginUploadFolder{
  function __construct() {
    wfPlugin::includeonce('wf/yml');
    wfPlugin::includeonce('string/match');
    wfPlugin::enable('wf/embed');
    wfPlugin::includeonce('download/safe');
    /**
     * Secure request param file.
     */
    if(strstr(wfRequest::get('file'), '..') || strstr(wfRequest::get('file'), '/')){
      throw new Exception(__CLASS__.' says: Error in request param file '.wfRequest::get('file').'!');
      exit;
    }
    if(strstr(wfRequest::get('new_file'), '..') || strstr(wfRequest::get('new_file'), '/')){
      throw new Exception(__CLASS__.' says: Error in request param new_file '.wfRequest::get('new_file').'!');
      exit;
    }
  }
  private function set_data($data){
    $data = new PluginWfArray($data);
    /**
     * Role
     */
    if(!$data->get('data/role')){
      $data->set('data/role/0', 'webmaster');
    }
    $valid = false;
    foreach ($data->get('data/role') as $key => $value) {
      if(wfUser::hasRole($value)){
        $valid = true;
        break;
      }
    }
    if(!$valid){
      exit('Role issue!');
    }
    /**
     * Replace dir
     */
    $data->set('data/path', wfSettings::replaceDir($data->get('data/path')));
    /**
     * Method before
     */
    if($data->get('data/method/before')){
      foreach ($data->get('data/method/before') as $key => $value) {
        $i = new PluginWfArray($value);
        $data = PluginUploadFolder::runCaptureMethod($i->get('plugin'), $i->get('method'), $data);
      }
    }
    /**
     * Path exist
     */
    $data->set('data/path_exist', wfFilesystem::fileExist(wfGlobals::getAppDir().$data->get('data/path')));
    /**
     * Files
     */
    $files = wfFilesystem::getScandir(wfGlobals::getAppDir().$data->get('data/path'));
    $files2 = array();
    foreach ($files as $key => $value) {
      $type = mime_content_type(wfGlobals::getAppDir().$data->get('data/path').'/'.$value);
      $button_group = new PluginWfYml(__DIR__.'/widget/button_group.yml');
      $button_group->setByTag(array('value' => $value));
      if(filetype(wfGlobals::getAppDir().$data->get('data/path').'/'.$value)!='file'){
        continue;
      }
      if($type=='image/jpeg'){
        $name = '<a href=# onclick="PluginUploadFolder.view(\''.$value.'\')">'.$value.'</a>';
        $button_group->setByTag(array('view_disabled' => ''));
      }else{
        $name = '<a href=# onclick="PluginUploadFolder.download(\''.$value.'\')">'.$value.'</a>';
        $button_group->setByTag(array('view_disabled' => 'disabled'));
      }
      $files2[$value] = array(
          'name' => $name,
          'size' => round(filesize(wfGlobals::getAppDir().$data->get('data/path').'/'.$value) / 1000, 2).' kb', 
          'created_at' => date('Y-m-d H:i:s', filemtime(wfGlobals::getAppDir().$data->get('data/path').'/'.$value)), 
          'type' => $type,
          'action' => array(array('type' => 'span', 'innerHTML' => $button_group->get()))
          );
    }
    $data->set('data/files', $files2);
    $data->set('data/sizeof_files', sizeof($files2));
    /**
     * Type
     */
    if($data->get('data/type')){
      $text = null;
      foreach ($data->get('data/type') as $key => $value) {
        $text .= ', '.$value;
      }
      $text = substr($text, 2);
      $data->set('data/type_text', $text);
    }
    /**
     * Name
     */
    if($data->get('data/name')){
      $text = null;
      foreach ($data->get('data/name') as $key => $value) {
        $text .= ', '.$value;
      }
      $text = substr($text, 2);
      $data->set('data/name_text', $text);
    }
    /**
     * Size
     */
    $data->set('data/max_size_text', $data->get('data/max_size').' MB');
    
    
    return $data;
  }
  public static function runCaptureMethod($plugin, $method, $form){
    wfPlugin::includeonce($plugin);
    $obj = wfSettings::getPluginObj($plugin);
    return $obj->$method($form);
  }
  public static function widget_include(){
    $element = array();
    $element[] = wfDocument::createWidget('wf/embed', 'embed', array('file' => '/plugin/upload/folder/js/PluginUploadFolder.js', 'type' => 'script'));    
    wfDocument::renderElement($element);
  }
  public function widget_folder($data){
    
    //wfHelp::yml_dump(wfUser::getSession()->get('plugin/upload/folder/file'));
    
    
    $data = $this->set_data($data);
    
    $script = 'PluginUploadFolder.data = '.json_encode($data->get('data')).';';
    
    /**
     * Rename last upload
     */
    if(wfUser::getSession()->get('plugin/upload/folder/file') && !wfUser::getSession()->get('plugin/upload/folder/file/rename')){
      wfUser::setSession('plugin/upload/folder/file/rename', true);
      $script .= "PluginUploadFolder.rename('".wfUser::getSession()->get('plugin/upload/folder/file/name')."');";
    }
    
    /**
     * 
     */
    $widget = new PluginWfYml(__DIR__.'/widget/folder.yml');
    $widget->setByTag($data->get('data'));
    $widget->setByTag(array('data' => $script), 'script');
    wfDocument::renderElement($widget->get());
    //wfHelp::textarea_dump($data->get('data'));
  }
  public function widget_capture($data){
    $data = $this->set_data($data);
    /**
     * 
     */
    if(!$data->get('data/path_exist')){
      mkdir(wfGlobals::getAppDir().$data->get('data/path'), 0777, true);
    }
    /**
     * 
     */
    $json = new PluginWfArray();
    $json->set('success', false);
    $json->set('data', $data->get('data'));
    /**
     * 
     */
    if(!wfFilesystem::fileExist(wfGlobals::getAppDir().$json->get('data/path'))){
      throw new Exception(__CLASS__." says: Dir ".$json->get('data/path')." does not exist!");
    }
    /**
     * 
     */
    if(wfRequest::isPost()){
      $json->set('file', $_FILES["file1"]);
      $json->set('success', true);
      /**
       * Validation of type, name and size should be equal in php/js.
       */
      /**
       * Type
       */
      if($json->get('success') && $json->get('data/type')){
        $valid = false;
        foreach ($json->get('data/type') as $key => $value) {
          if($value==$json->get('file/type')){
            $valid = true;
          }
        }
        if($valid==false){
          $json->set('success', false);
          $json->set('error', 'File type issue...');
        }
      }
      /**
       * Name
       */
      if($json->get('success') && $json->get('data/name')){
        $match = new PluginStringMatch();
        $valid = false;
        foreach ($json->get('data/name') as $key => $value) {
          if($match->wildcard($value, strtolower($json->get('file/name'))) > 0){
            $valid = true;
          }
        }
        if($valid==false){
          $json->set('success', false);
          $json->set('error', 'File name issue...');
        }
      }
      /**
       * Size
       */
      if($json->get('success') && $json->get('data/max_size') && $json->get('file/size')> ($json->get('data/max_size')*1000000)){
        $json->set('success', false);
        $json->set('error', 'File size issue...');
      }
      /**
       * Save
       */
      if($json->get('success')){
        $move = move_uploaded_file($json->get('file/tmp_name'), wfGlobals::getAppDir().$json->get('data/path').'/'.$json->get('file/name'));
        wfUser::setSession('plugin/upload/folder/file', $json->get('file'));
        $json->set('move', $move);
      }
    }else{
      if(wfRequest::get('action')=='delete'){
        /**
         * Delete
         */
        if(wfFilesystem::fileExist(wfGlobals::getAppDir().$json->get('data/path').'/'.wfRequest::get('file'))){
          wfFilesystem::delete(wfGlobals::getAppDir().$json->get('data/path').'/'.wfRequest::get('file'));
          $json->set('success', true);
          if($data->get('data/sizeof_files')==1){
            /**
             * Delete folder if only one file left
             */
            wfFilesystem::delete_dir(wfGlobals::getAppDir().$json->get('data/path'));
          }
        }
      }elseif(wfRequest::get('action')=='download'){
        /**
         * 
         */
        wfUser::setSession('plugin/download/safe/file', wfGlobals::getAppDir().$data->get('data/path').'/'.wfRequest::get('file'));
        /**
         * 
         */
        $download = new PluginDownloadSafe();
        $download->widget_safe();
        exit;
      }elseif(wfRequest::get('action')=='view'){
        exit('<img src="'.$data->get('data/url').'&file='.wfRequest::get('file').'&action=download" />');
      }elseif(wfRequest::get('action')=='rename'){
        $json->set('success', true);
        
        /**
         * Name
         */
        if($json->get('success') && $json->get('data/name')){
          $match = new PluginStringMatch();
          $valid = false;
          foreach ($json->get('data/name') as $key => $value) {
            if($match->wildcard($value, strtolower(wfRequest::get('new_file'))) > 0){
              $valid = true;
            }
          }
          if($valid==false){
            $json->set('success', false);
            $json->set('error', 'File rename issue...');
          }
        }
        
        if($json->get('success')){
          $x = rename(wfGlobals::getAppDir().$data->get('data/path').'/'.wfRequest::get('file'), wfGlobals::getAppDir().$data->get('data/path').'/'.wfRequest::get('new_file'));
          //wfHelp::yml_dump($x);
        }
        
      }
    }
    exit(json_encode($json->get()));
  }
  public function method_before($data){
    if(true){
      /**
       * One should create it's own method like this one if in need of change data on the fly.
       */
      if(wfRequest::get('id')){
        if(strstr(wfRequest::get('id'), '.') || strstr(wfRequest::get('id'), '/')){
          exit('Some hack prevention...');
        }
        $data->set('data/path', $data->get('data/path').'/'.wfRequest::get('id'));
        $data->set('data/url', $data->get('data/url').'?id='.wfRequest::get('id'));
      }
    }
    return $data;
  }
}
