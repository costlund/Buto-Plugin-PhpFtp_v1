<?php
class PluginPhpFtp_v1{
  public $data = null;
  public $conn = null;
  public $login = null;
  public $file_list = array();
  public $folder_list = array();
  public $dir = null;
  private $top_level = null;
  function __construct() {
    $this->data = new PluginWfArray();
    $this->data->set('server',   null);
    $this->data->set('user',     null);
    $this->data->set('password', null);
    wfPlugin::includeonce('wf/yml');
    /**
     * Trying to get default settings for this plugin.
     */
    $data = new PluginWfYml(__DIR__.'/../../../../buto_data/plugin/php/ftp_v1/settings.yml');
    $this->data = new PluginWfArray($data->get('ftp'));
    $this->dir = $data->get('ftp/dir');
  }
  public function setData($data){
    $this->data = new PluginWfArray($data);
  }
  public function conn(){
    $this->conn = ftp_connect($this->data->get('server')) or die("Could not connect to ".$this->data->get('server')."!");
    return null;
  }
  public function close(){
    ftp_close($this->conn);
  }
  public function login(){
    $this->login = ftp_login($this->conn, $this->data->get('user'), $this->data->get('password'));
  }
  public function set_file_list($dir = null){
    $this->folder_list = array();
    $this->file_list = array();
    if($dir){
      $this->dir = $dir;
    }
    if(!$this->dir){
      throw new Exception('PluginPhpFtp_v1 says: Param dir is not set!');
    }
    $this->conn();
    $this->login();
    $this->set_file_list2($this->dir);
    $this->close();
  }
  private function set_file_list2($dir){
    $folder = substr($dir, strlen($this->dir));
    $rawlist = ftp_rawlist($this->conn, $dir);
    /**
     * Set folder list.
     */
    $this->folder_list[$folder]['item'] = sizeof($rawlist);
    /**
     * 
     */
    foreach ($rawlist as $key => $value) {
      $parsed = $this->parse_rawlist_value($value);
      if($parsed['name']=='.' || $parsed['name']=='..'){
      }elseif($parsed['isdir']){
        $this->set_file_list2($dir.'/'.$parsed['name']);
      }else{
        $dir2 = substr($dir.'/'.$parsed['name'], strlen($this->dir));
        $dir2 = str_replace('/public_html', '/[web_folder]', $dir2);
        $this->file_list[$dir2] = array('remote_size' => $parsed['size'], 'remote_time' => $parsed['remote_time']);
      }
    }
  }
  /**
   * 
   * @param type $remote_file
   * @param type $local_file
   * @return type
   */
  public function put($remote_file, $local_file){
    /**
     * Make folders if not exist.
     */
    $dirname = dirname($remote_file);
    $dir_split = preg_split("#/#", substr($dirname, 1)); 
    $dir_str = null;
    $dir_array = array();
    foreach ($dir_split as $key => $value) {
      $dir_str = $dir_str.'/'.$value;
      $dir_array[$dir_str] = $this->rawlist($dir_str);
    }
    foreach ($dir_array as $key => $value) {
      if(!sizeof($value)){
        $this->mdir($key);
      }
    }
    /**
     * 
     */
    $this->conn();
    $this->login();
    if(false){
      /**
       * PNG images are corrupted using FTP_ASCII.
       */
      $bool = ftp_put($this->conn, $this->dir.$remote_file, $local_file, FTP_ASCII);
    }else{
      $bool = ftp_put($this->conn, $this->dir.$remote_file, $local_file, FTP_BINARY);
    }
    $this->close();
    return $bool;
  }
  /**
   * 
   * @param type $local_file
   * @param type $remote_file
   * @return type
   */
  public function get($local_file, $remote_file){
    $this->conn();
    $this->login();
    $bool = ftp_get($this->conn, $local_file, $this->dir.$remote_file, FTP_ASCII);   
    $this->close();
    return $bool;
  }
  /**
   * 
   * @param type $remote_file
   * @return type
   */
  public function delete($remote_file){
    $this->conn();
    $this->login();
    $bool = ftp_delete($this->conn, $this->dir.$remote_file);   
    $this->close();
    return $bool;
  }
  /**
   * Delete folder (has to be empty).
   * @param type $remote_dir
   * @return type
   */
  public function rmdir($remote_dir){
    $this->conn();
    $this->login();
    $bool = ftp_rmdir($this->conn, $this->dir.$remote_dir);   
    $this->close();
    return $bool;
  }
  /**
   * 
   * @param type $dir
   * @return type
   */
  public function mdir($dir){
    $this->conn();
    $this->login();
    $bool = ftp_mkdir($this->conn, $this->dir.$dir);   
    $this->close();
    return $bool;
  }
  public function raw_list_top_level_7($rawlist){
    /**
     * PHP method ftp_rawlist is limited to 7 levels.
     * If method rawlist set top_level to 7 this method keep checking for 7 levels more.
     */
    if($this->top_level=7){
      foreach ($rawlist as $key => $value) {
        $i = new PluginWfArray($value);
        if($i->get('levels')==7 && $i->get('split/isdir')){
          $rawlist2 = $this->rawlist($this->replace_web_folder($i->get('name')));
          $rawlist = array_merge($rawlist, $rawlist2);
        }
      }
    }
    return $rawlist;
  }
  public function widget_demo_rawlist($data){
    $data = new PluginWfArray($data);
    wfPlugin::includeonce('php/ftp_v1');
    $ftp = new PluginPhpFtp_v1();
    $ftp->setData($data->get('data/ftp'));
    $ftp->dir = $data->get('data/ftp/dir');
    $rawlist = $ftp->rawlist();
    $rawlist = $ftp->raw_list_top_level_7($rawlist);
    $remote_files = $ftp->rawlist_files($rawlist);
    wfHelp::textarea_dump($remote_files);
  }
  public function page_demo(){
    wfHelp::yml_dump(array('data' => $this->data->get(), 'dir' => $this->dir));
    if(false){
      $this->set_file_list();
      wfHelp::yml_dump(array('sizeof' => sizeof($this->file_list), 'file_list' => $this->file_list), true);
    }
    if(false){
      $rawlist = $this->rawlist();
      $rawlist = $this->raw_list_top_level_7($rawlist);
    }
    if(false){
      wfHelp::yml_dump(array('top_level' => $this->top_level, 'sizeof' => sizeof($rawlist), 'rawlist' => $rawlist), true);
    }
    if(false){
      $rawlist_files = $this->rawlist_files($rawlist);
      wfHelp::yml_dump(array('top_level' => $this->top_level, 'sizeof' => sizeof($rawlist_files), 'rawlist_files' => $rawlist_files), true);
    }
    if(false){
      $rawlist_folders = $this->rawlist_folders($rawlist);
      wfHelp::yml_dump(array('top_level' => $this->top_level, 'sizeof' => sizeof($rawlist_folders), 'rawlist_folders' => $rawlist_folders), true);
    }
    $this->set_file_list();
    wfHelp::yml_dump($this->file_list);
  }
  private function nlist($directory = "/"){
    $this->conn();
    $this->login();
    $file_list = ftp_nlist($this->conn, $directory);
    $this->close();
    return $file_list;
  }
  private function replace_web_folder($str){
    return str_replace('/[web_folder]', '/public_html', $str);
  }
  public function rawlist($directory = null, $replace = array('/public_html' => '/[web_folder]')){
    /**
     * Method ftp_rawlist does not seems to get files more then 7 levels.
     * Use set_file_list() instead despite it´s slower.
     */
    $this->conn();
    $this->login();
    $raw_list = ftp_rawlist($this->conn, $this->dir.$directory, true);    
    $this->close();
    $parse = $this->parse_rawlist($raw_list, $directory, $replace);
    return $parse;
  }
  private function parse_rawlist_value($value){
    $split = preg_split("[ ]", $value, 9, PREG_SPLIT_NO_EMPTY);
    $parsed = array();
    if ($split[0] != "total") {
      $parsed['isdir']     = $split[0]{0} === "d";
      $parsed['perms']     = $split[0];
      $parsed['number']    = $split[1];
      $parsed['owner']     = $split[2];
      $parsed['group']     = $split[3];
      $parsed['size']      = $split[4];
      $parsed['month']     = $split[5];
      $parsed['day']       = $split[6];
      $parsed['time']      = $split[7];
      $parsed['name']      = $split[8];
      $year = (int)date('Y');
      $time_now = time();
      $time = strtotime("".$parsed['day']." ".$parsed['month']." $year ".$parsed['time']."");
      if($time>$time_now){
        /**
         * Back one year.
         */
        $year--;
        $time = strtotime("".$parsed['day']." ".$parsed['month']." $year ".$parsed['time']."");
      }
      $parsed['remote_time'] = $time;
    }
    return $parsed;
  }
  private function parse_rawlist($rawlist, $start_dir, $replace)
  {
    array_unshift($rawlist, '');
    array_unshift($rawlist, $this->dir.':');
    $this->top_level = 0;
    $remote_files = array();
    $i = -1;
    $folder_name = $start_dir;
    foreach ($rawlist as $key => $value) {
      if(!$value){
        continue;
      }
      $folder = false;
      if(substr($value, 0, 1)=='/'){
        $folder = true;
        $folder_name = substr($value, 0, strlen($value)-1);
        $folder_name = substr($folder_name, strlen($start_dir));
        $folder_name = substr($folder_name, strlen($this->dir));

        $folder_name = $start_dir.$folder_name;
        /**
         * replace...
         */
        $folder_name = str_replace('/public_html', '/[web_folder]', $folder_name);
      }
      if(!$folder){
        $parsed = $this->parse_rawlist_value($value);
        if (sizeof($parsed)) {
          if($parsed['name']=='.' || $parsed['name']=='..'){
            continue;
          }
        }
        $i++;
        $remote_files[$i]['name'] = null;
        $remote_files[$i]['levels'] = null;
        $remote_files[$i]['remote_size'] = null;
        $remote_files[$i]['remote_time'] = null;
        $remote_files[$i]['folder'] = $folder_name;
        $remote_files[$i]['raw'] = $value;
        if (sizeof($parsed)) {
          $levels = substr_count($folder_name, '/');
          /**
           * name
           */
          $remote_files[$i]['name'] = $folder_name.'/'.$parsed['name'];
          $remote_files[$i]['levels'] = $levels;
          /**
           * Set top level.
           */
          if($levels>$this->top_level){
            $this->top_level = $levels;
          }
          /**
           * remote_size
           */
          $remote_files[$i]['remote_size'] = $parsed['size'];
          /**
           * remote_time
           */
          $remote_files[$i]['remote_time'] = $parsed['remote_time'];
        }
        $remote_files[$i]['split'] = $parsed;
      }
    }
    return $remote_files;
  }  
  public function rawlist_files($rawlist){
    $temp = array();
    foreach ($rawlist as $key => $value) {
      $item = new PluginWfArray($value);
      if(!$item->get('split/isdir')){
        $temp[$item->get('name')] = array('remote_size' => $item->get('remote_size'), 'remote_time' => $item->get('remote_time'), 'levels' => $item->get('levels'));
      }
    }
    return $temp;
  }
  public function rawlist_folders($rawlist){
    $temp = array();
    foreach ($rawlist as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('split/isdir')){
        if($item->get('name')=='/.' || $item->get('name')=='/..'){
//          continue;
        }
        $temp[$item->get('name')] = array('remote_size' => $item->get('remote_size'), 'remote_time' => $item->get('remote_time'), 'levels' => $item->get('levels'));
      }
    }
    return $temp;
  }
}
