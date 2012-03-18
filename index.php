<!DOCTYPE html>
<html>
    <head>
        <title>
            <?php 
				require("stuff/config.php");
				echo constant('SITE_NAME');
				?>
        </title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="description" content="Tslmy's personal blog, powered by t.t.t, the simplest plain-text-based, database-free blog engine."
        />
        <meta name="keywords" content="t.t.t powered,blog,tslmy,personal,chinese,english,geek"
        />
		<link href="stuff/favicon.ico" rel="bookmark" type="image/x-icon" />
		<link href="stuff/rss.php" type="application/atom+xml" rel="alternate" title="<?php echo constant('SITE_NAME'); ?> R.S.S." />
		<!--link href='http://fonts.googleapis.com/css?family=Muli' rel='stylesheet' type='text/css'-->
        <link href="stuff/css/style_list.css" rel="stylesheet" type="text/css" />
        <!-- below to </head>: Google Analytics Code. -->
        <script type="text/javascript">
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-21290300-1']);
            _gaq.push(['_trackPageview']); (function() {
                var ga = document.createElement('script');
                ga.type = 'text/javascript';
                ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl': 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(ga, s);
            })();
        </script>
    </head>
    <body>
        <div id="main">
			<?php
			if ($handler = opendir("content/")){  //try to open the directory.
				while (false !== ($filename = readdir($handler))) {//for each file in this directory
					$len=strlen($filename);//get the length of the file name for the next step
					if (substr($filename,0,1)!="_" && strtolower(substr($filename,$len-4,$len))==".txt") { //if this file is not intended to be omitted and it's a .txt file 
						$files[filectime("content/".$filename)] = substr($filename,0,$len-4); //then put it into the file array with its Last Modified Time as its number
					}
				}
				krsort($files,SORT_NUMERIC);//sort the array out
				
				if (constant('LIST_MODE')==0) {
					include_once "stuff/markdown.php";
					include_once "stuff/smartypants.php";
					include_once "stuff/get_content.php";
				}
				
				function closetags($html)
					{
						/*get all content BEFORE the last "<", ensuring every HTML tag in the content is finished with a ">"*/
						$html = preg_replace("~<[^<>]+?$~i", "", $html);
						/*start to finish all unfinished tags*/
						#put all opened tags into an array
						preg_match_all("#<([a-z]+)( .*[^/])?(?!/)>#iU", $html, $result);
						$openedtags = $result[1];
						#put all closed tags into an array
						preg_match_all("#</([a-z]+)>#iU", $html, $result);
						$closedtags = $result[1];
						$len_opened = count($openedtags);
						# all tags are closed
						if (count($closedtags) == $len_opened) {
							return $html;
						}
						$openedtags = array_reverse($openedtags);
						# close tags
						for ($i = 0; $i < $len_opened; $i++) {
							if (!in_array($openedtags[$i], $closedtags)) {
								$html .= '</' . $openedtags[$i] . '>';
							} else {
								unset($closedtags[array_search($openedtags[$i], $closedtags)]);
							}
						}
						return $html;
					}

				//doing the page number math START
				if (isset($_GET["page"])){ //try to get the target page number
					$this_page=$_GET["page"];
				}else{//if failed, then the user has reached here by typing just the domain.
					$this_page=1;
				}
				//doing the page number math END
				$prev_items_to_omit=($this_page-1)*constant('ITEMS_DISPLAYED_PEER_PAGE');
				$count=0;//set counter to zero
				$items_limit=$prev_items_to_omit+constant('ITEMS_DISPLAYED_PEER_PAGE');
				$current_date_year='';
				$current_date_month='';
				foreach ($files as $each_one){
					if ($count<$items_limit){
						$count++;
						if ($count>$prev_items_to_omit){
							$this_file_path="content/".$each_one.".txt";
							//labeling year and month START
							$this_mtime=filemtime($this_file_path);
							$this_modified_year=date("Y",$this_mtime);
							if ($current_date_year<>$this_modified_year) {
								echo "<div class='item date year'>".$this_modified_year.'</div>';
								$current_date_year=$this_modified_year;
								$current_date_month='';//reset month
							};
							$this_modified_month=date("F",$this_mtime);
							if ($current_date_month<>$this_modified_month) {
								echo "<div class='item date'>".$this_modified_month.'</div>';
								$current_date_month=$this_modified_month;
							};
							//labeling year and month END
							echo 	"<div class='item'>
										<a href='view.php?name=".$each_one."'>
											<span class='effect'>
												<!--span class='prefix'-->
													<span class='day'>".date("d",$this_mtime)."</span> 
												<!--/span-->
												<span class='name'>".$each_one."</span>
											</span>
										</a>
										<article><span class='mtime'>".date("H:i",$this_mtime)."</span>";//things to start a new block for a post

							if (constant('LIST_MODE')==0) {//0(default, takes up more CPU):  Renders everything from Markdown everytime they are needed.
								echo closetags(substr(get_content($this_file_path),0,constant('PREVIEW_SIZE_IN_KB')));
							} else {//1(recommended, takes up more disk storage and PHP's writing permission): Make a HTML cache for every new/updated post when index.php finds one, and then everyone else reads directly from cache.
								$cache_file_path="cache/".$each_one.".htm";
								if ((file_exists($cache_file_path)==false) or (filemtime($cache_file_path)<filemtime($this_file_path))) {
								//if the corresponding cache file does not exist or havn't been updated since the last time that this post changed
									if (function_exists('Markdown')==false){
										include_once "stuff/markdown.php";
										include_once "stuff/smartypants.php";
										include_once "stuff/get_content.php";
									}
									fwrite(fopen($cache_file_path,"w+"),get_content($this_file_path));//try "fgetss" sometime.
									echo "[NEW]";
								}
								echo closetags(fread(fopen($cache_file_path, "r"),constant('PREVIEW_SIZE_IN_KB')));
							}
							
							echo		"...</article>
										<div class='hr'></div>
									</div>
									\n";
						}
					}
					else
					{
						break;
					}
				}
			}else { //if failed to load the directory.
				echo "Error occured. Contact tslmy!";
			}
			?>
		</div>
		<div id="left">
			<div id="left_texts">
				<div id="logo">
					<a href="http://tslmy.tk">
						<?php echo constant('SITE_NAME');?>
					</a>
				</div>
				<div id="nav_holder">
					
						<?php
							$url=$_SERVER["REQUEST_URI"]; //get the current URL
							$max_page_number=ceil(count($files)/constant('ITEMS_DISPLAYED_PEER_PAGE'));
							//echo $max_page_number." pages for ".count($files)." items.<br/>";
							if ($max_page_number>1) {
								for ($page_number=1; $page_number<=$max_page_number; $page_number++){
									if ($page_number==$this_page) {
										echo "<div style='background:rgba(150,150,150,.5); pointer-events:none;' class='nav_button'><b>".$page_number." </b></div>";
									} else {
										echo "<a href='index.php?page=".$page_number."'><div class='nav_button'>".$page_number."</div></a>";
									}
								}
							}
						?>
				</div>
				<div id="intro">
				   <?php
				   $intro_file_name="content/_intro.txt";
				   if(file_exists($intro_file_name)){
						$file=fopen( $intro_file_name, "r");
						while(!feof($file))	{
							echo "<p>".fgets($file). "</p>";
						}
						fclose($file);
						} else {
							echo "Just another t.t.t-powered minimal blog.";
						}
					?>
				</div>
			</div>
       </div>
    </body>
</html>