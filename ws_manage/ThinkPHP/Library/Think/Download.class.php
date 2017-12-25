<?php
namespace Think;

class Download {

	private $config=[];

	public function __construct($config=[]){
		$this->initialize($config);
	}

	public function initialize($config=[]){
		$this->config=array_merge($this->config,$config);
		return $this;
	}

	public function start(){
		if (!is_file($this->config['file'])){
			E('需要下载的文件不存在');
		}

		//$fileInfo=new \Finfo(FILEINFO_MIME_TYPE);
		//$fileMime=$fileInfo->file($this->config['file']);
		$fileSize=filesize($this->config['file']);
		// 文件传输类型，文件大小，文件名称，附件方式传送
		//header('Content-type:'.$fileMime);
		header('Content-length:'.$fileSize);
		header('Content-disposition:attachment;filename='.$this->config['name']);

		$source=fopen($this->config['file'],'r');
		while(!feof($source)){
			echo fgets($source,4095);
		}
		fclose($source);
	}
}

?>