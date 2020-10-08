function PluginUploadFolder(){
  this.data = {};
  this.file = null;
  this.validate = function(){
    var btn = document.getElementById('upload_button');
    var valid = false;
    PluginUploadFolder.file = document.getElementById('puf_input_file').files[0];
    /**
     * Validation of type, name and size should be equal in php/js.
     */
    /**
     * Type
     */
    if(PluginUploadFolder.data.type){
      valid = false;
      for(var i=0; i<PluginUploadFolder.data.type.length; i++){
        if(PluginUploadFolder.data.type[i]==PluginUploadFolder.file.type){
          valid = true;
        }
      }
      if(valid==false){
        alert('This file type is not valid ('+PluginUploadFolder.file.type+').');
        return null;
      }
    }
    /**
     * Name
     */
    if(PluginUploadFolder.data.name){
      valid = false;
      for(var i=0; i<PluginUploadFolder.data.name.length; i++){
        if(this.match(PluginUploadFolder.file.name.toLowerCase(), PluginUploadFolder.data.name[i].toLowerCase())){
          valid = true;
        }
      }
      if(valid==false){
        alert('This file name is not valid ('+PluginUploadFolder.file.name+').');
        return null;
      }
    }
    /**
     * Size
     */
    if(PluginUploadFolder.file.size>PluginUploadFolder.data.max_size*1000000){
      alert('Files has exceeded max size of '+(PluginUploadFolder.data.max_size*1000000)+' with '+(PluginUploadFolder.file.size)+'!');
      return null;
    }
    /**
     * 
     */
    btn.disabled = false;
    btn.click();
  }
  this.match = function(str, rule) {
    var ex = (str) => str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
    return new RegExp("^" + rule.split("*").map(ex).join(".*") + "$").test(str);
  }  
  this.uploadFile = function(){
    var form_data = new FormData();
    form_data.append("file1", PluginUploadFolder.file);
    var ajax = new XMLHttpRequest();
    ajax.upload.addEventListener("progress", this.progressHandler, false);
    ajax.addEventListener("load",  this.loadHandler,  false);
    ajax.open("POST", PluginUploadFolder.data.url);
    ajax.send(form_data);
  }
  this.progressHandler = function(e){
    var percent = (e.loaded / e.total) * 100;
    $('#puf_btn_upload').hide();
    $('#puf_progress').show();
    $('#puf_progress').value = Math.round(percent);
  }
  this.loadHandler = function(e){
    if(e.target.status=='200'){
      console.log(e.target.responseText.trim());
      var json = JSON.parse(e.target.responseText.trim());
      if(json.success == true){
        if(PluginUploadFolder.data.success){
          eval(PluginUploadFolder.data.success)
        }else{
          alert('File was uploaded (set your custom success param to replace this alert)!');
        }
      }else{
        alert(json.error);
      }
    }else{
      alert('An error occured, status '+e.target.status+'.')          
    }
  }
  this.delete = function(file){
    if(!confirm('Are you sure?')){
      return null;
    }
    $.get( PluginUploadFolder.data.url, { file: file, action: 'delete' } )
      .done(function( data ) {
        if(PluginUploadFolder.data.success){
          eval(PluginUploadFolder.data.success)
        }else{
          alert('File was deleted (set your custom success param to replace this alert)!');
        }
      });    
  }
  this.download = function(file){
    var href = PluginUploadFolder.data.url;
    file = encodeURIComponent(file);
    if(href.indexOf('?')!=-1){
      href += '&file='+file;
    }else{
      href += '?file='+file;
    }
    href += '&action=download';
    window.open(href);
    return null;
  }
  this.view = function(file){
    var href = PluginUploadFolder.data.url;
    file = encodeURIComponent(file);
    if(href.indexOf('?')!=-1){
      href += '&file='+file;
    }else{
      href += '?file='+file;
    }
    href += '&action=view';
    window.open(href, '_blank');
    return null;
  }
  this.rename = function(file){
    PluginUploadFolder.data.rename_file = file;
    PluginWfBootstrapjs.modal({id: 'modal_puf_rename', content: '', label: PluginI18nJson_v1.i18n('Rename'), fadezzz: false});
    var data = [
      {type: 'div', innerHTML: 
        [
          {type: 'input', attribute: {id: 'file', type: 'text', value: file, class: 'form-control', 'onfocus': 'this.select()'}}
        ], attribute: {}
      },
      {type: 'div', innerHTML: 
        [
          {type: 'button', innerHTML: 'Save', attribute: {class: 'btn btn-primary', onclick: 'PluginUploadFolder.rename_do()'}}
        ], attribute: {}
      }
    ];
    PluginWfDom.render(data, 'modal_puf_rename_body');
    return null;
  }
  this.rename_do = function(){
    $.get( PluginUploadFolder.data.url, { file: PluginUploadFolder.data.rename_file, new_file: $('#modal_puf_rename #file').val(), action: 'rename' } )
      .done(function( data ) {
        var json = JSON.parse(data);
        if(json.success == false){
          alert(json.error);
        }else{
          if(PluginUploadFolder.data.success){
            $('#modal_puf_rename').modal('hide');
            eval(PluginUploadFolder.data.success);
          }else{
            alert('File was renamed (set your custom success param to replace this alert)!');
          }
        }
      });    
  }
}
var PluginUploadFolder = new PluginUploadFolder();
