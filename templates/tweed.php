<form action="/directory/index/tweedle/" method="post">

<div>
	<textarea style="width: 500px; height: 500px;" name="fastadd"></textarea>
	<input type="hidden" name="rootid" value="1" />
</div>

<div>
	<input type="submit" value="Try" />
</div>


</form>

<?php 

if(isset($this->tplVar['menu'])) {
	print $this->tplVar['menu'];
}
?>


<script>
//var nestable = UIkit.nestable(element, { /* options */ });
</script>
                            