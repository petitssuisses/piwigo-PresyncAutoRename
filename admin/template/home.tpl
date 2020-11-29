{combine_css path=$PRESYNCAUTORENAME_PATH|@cat:"admin/template/style.css"}

<div class="titrePage">
  <h2>{'Autorename directories and filenames in gallery '|@translate}: <a href="{$SITE_URL}">{$SITE_URL}</a></h2>
</div>
<h4></h4>
<p'>{'presyncautorename_intro'|@translate}</p>

{if not empty($sync_errors)}
<h3>{'Error list'|@translate}</h3>
<div class="errors">
<ul>
  {foreach from=$sync_errors item=error}
  <li>[{$error.ELEMENT}] {$error.LABEL}</li>
  {/foreach}
</ul>
</div>
<h3>{'Errors caption'|@translate}</h3>
<ul>
  {foreach from=$sync_error_captions item=caption}
  <li><strong>{$caption.TYPE}</strong>: {$caption.LABEL}</li>
  {/foreach}
</ul>
{/if}

{if not empty($sync_infos)}
<h3>{'Rename process results'|@translate}</h3>
<div class="infos">
<ul>
  {foreach from=$sync_infos item=info}
  <li>{$info.ELEMENT} {$info.LABEL}</li>
  {/foreach}
</ul>
</div>
{else}
<h3>{'Rename process results'|@translate}</h3>
<div class="infos">
<p>{'The scan did not find any file which do not conform to required naming pattern'|@translate}</p>
</div>
{/if}


<h4>{'Choose an option'|@translate}</h4>
<form action="" method="post" id="update">

  <fieldset id="syncFiles">
	<legend>{'Fix invalid names for '|@translate}</legend>
	<ul>
		<li><label><input type="radio" name="sync" value="dirs" {if 'dirs'==$introduction.sync}checked="checked"{/if}> {'only directories'|@translate}</label></li>

		<li><label><input type="radio" name="sync" value="files" {if 'files'==$introduction.sync}checked="checked"{/if}> {'directories and files'|@translate}</label></li>
	</ul>
  </fieldset>

  <fieldset id="syncSimulate">
    <legend>{'Simulation'|@translate}</legend>
    <ul><li><label><input type="checkbox" name="simulate" value="1" checked="checked"> {'Simulation mode (no change will be made on the filesystem)'|@translate}</label></li></ul>
  </fieldset>

  <fieldset id="catSubset">
    <legend>{'reduce to single existing albums'|@translate}</legend>
    <ul>
    <li>
    <select class="categoryList" name="cat" size="10">
      {html_options options=$category_options selected=$category_options_selected}
    </select>
    </li>

    <li><label><input type="checkbox" name="subcats-included" value="1" {if $introduction.subcats_included}checked="checked"{/if}> {'Search in sub-directories'|@translate}</label></li>
    </ul>
  </fieldset>

  <p class="bottomButtons">
    <input class="submit" type="submit" value="{'Submit'|@translate}" name="submit">
    <input class="submit" type="reset"  value="{'Reset'|@translate}"  name="reset">
  </p>
</form>
