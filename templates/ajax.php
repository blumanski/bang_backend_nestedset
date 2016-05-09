<?php 
header('Content-type:application/json;charset=utf-8');
if(isset($this->tplVar['response'])) {
	echo $this->tplVar['response'];
}