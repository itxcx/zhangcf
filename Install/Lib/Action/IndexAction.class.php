<?php
class IndexAction extends Action {
    public function index()
	{
        header("Location:xsinst.php?s=/Install/index");
    }
}
?>