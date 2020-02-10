<?php
if (isset($_REQUEST['topic'])) {
	$CurrentTopicID = db_string($_REQUEST['topic']);
} else {
	error(0);
}

$DB->query("SELECT Category, Title, Body, Time, MinClass, SubCat, ID FROM articles WHERE TopicID='$CurrentTopicID'");
if (!list($Category, $Title, $Body, $Time, $MinClass, $SubCat, $ArticleID) = $DB->next_record()) {
	error(404);
}
$Body = $Text->full_format($Body, true); // true so regardless of author permissions articles can use adv tags
$Body = replace_special_tags($Body);

if ($MinClass>0) { // check permissions
		// should there be a way for FLS to see these... perm setting maybe?
	if ( $StaffClass < $MinClass ) error(403);
}

$Articles = $Cache->get_value("articles_$Category");
if ($Articles===false) {
		$DB->query("SELECT TopicID, Title, Description, SubCat, MinClass
				  FROM articles
				 WHERE Category='$Category'
			  ORDER BY SubCat, Title");
		$Articles = $DB->to_array();
		$Cache->cache_value("articles_$Category", $Articles);
}

$PageTitle = empty($LoggedUser['ShortTitles'])?"{$ArticleCats[$Category]} > {$ArticleSubCats[$SubCat]} > $Title":$Title ;

show_header( $PageTitle, 'browse,overlib,bbcode');
?>

<div class="thin">
	<div class="sidebar">
    <div class="head"><?=$ArticleCats[$Category]?> Directory</div>
    <div class="pad">
      <form method="get" action="articles.php">
				<input name="searchtext" type="text" value="<?=htmlentities($Searchtext)?>" /> <input type="submit" value="Search Articles" class="center" /> <br/>
      </form>
      <br/>
      <?php list_articles($Articles, $StaffClass); ?>
    </div>
  </div>
	<div class="main_column">
		<h2><?=$PageTitle?>
			<?php if (check_perms('admin_manage_articles')) { ?>
				<a href="tools.php?action=editarticle&id=<?=$ArticleID?>">(Edit)</a>
			<?php } ?>
		</h2>
		<?=$Body ?>
  </div>
</div>
<div style="clear:both"></div>

<?php
show_footer();
