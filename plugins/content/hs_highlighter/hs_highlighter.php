<?php
/**
 * @version		1.0.12
 * @package		Hs - Highlighter 2014-01-21 
 * @author		Hayato Sugimoto
 * @copyright	Copyright (C) 2005 - 2014  Hayatos. All rights reserved.
 * @license		GNU/GPL
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgContentHs_highlighter extends JPlugin {

	private $_load = false;

	private $_i = 0;
	
	
	//private $_targetTag = array('com_content.', 'com_k2.', '');
	

	public function onContentPrepare($context, &$article, &$params, $page = 0) {

		if ($this -> _load === true) {
			return;
		}
		
		//load mootools
		JHtml::_('behavior.framework', true);
		
		/*
		//Only search article and k2		
		if (strpos($context,'com_content.')===false && strpos($context,'com_k2.')===false ) {
			return;
		}
		*/
		
		//check whether a content has a syntax highlighter code or not
		if (strpos($article -> text, 'class="brush:') === false) {
			return;
		}

		$plguin_lo = 'plugins/content/hs_highlighter/';
		$to_scripts = $plguin_lo . 'lib/scripts/';
		$to_src = $plguin_lo . 'lib/src/';
		$to_styles = $plguin_lo . 'lib/styles/';

		$doc = JFactory::getDocument();

		//whatever languages are used, this two files are must
		$doc -> addScript($to_scripts . 'shCore.js');
		$doc -> addScript($to_scripts . 'shAutoloader.js');

		$doc -> addStyleSheet($to_styles . 'shCore.css');

		//Switch a style from plugin prameter.
		$style = $this -> params -> get('style', '0');

		switch($style) {
			case '0' :
				$doc -> addStyleSheet($to_styles . 'shThemeDefault.css');
				break;
			case '1' :
				$doc -> addStyleSheet($to_styles . 'shThemeDjango.css');
				break;
			case '2' :
				$doc -> addStyleSheet($to_styles . 'shThemeEclipse.css');
				break;
			case '3' :
				$doc -> addStyleSheet($to_styles . 'shThemeEmacs.css');
				break;
			case '4' :
				$doc -> addStyleSheet($to_styles . 'shThemeFadeToGrey.css');
				break;
			case '5' :
				$doc -> addStyleSheet($to_styles . 'shThemeMidnight.css');
				break;
			case '6' :
				$doc -> addStyleSheet($to_styles . 'shThemeRDark.css');
				break;
			case '7' :
				$doc -> addStyleSheet($to_styles . 'shThemeCustom.css');
				break;
		}

		//params
		$config = "";

		if ($this -> params -> get('stripbrs', false) != "false") {
			$config .= "SyntaxHighlighter.config.stripBrs = true;";
		}
		$tag = trim($this -> params -> get('tag', 'pre'));
		if ($tag !== 'pre') {
			$config .= "SyntaxHighlighter.config.tagName = '" . $tag . "';";
		}
		
		if ($this -> params -> get('autolink', false) == "false") {
			$config .= "SyntaxHighlighter.defaults['auto-links'] = false;";
		}

		$cname = trim($this -> params -> get('class-name', null));
		if ($cname !== null && strlen($cname) > 0) {
			$config .= "SyntaxHighlighter.defaults['class-name'] = '" . $cname . "';";
		}
		if ($this -> params -> get('collapse', false) == "true") {
			$config .= "SyntaxHighlighter.defaults['collapse'] = true;";
		}
		/* XML file cannot load?
		 if($this->params->def('html-script', false) == "true"){
		 $config .= "SyntaxHighlighter.defaults['html-script'] = true;";
		 }
		 */
		if ($this -> params -> get('toolbar', true) == "false") {
			$config .= "SyntaxHighlighter.defaults['toolbar'] = false;";
		}

		//get other brushes
		$otherBrushes = trim($this -> params -> get('other-brushes', ''));
		$otherBrushesScript = '';

		if (strlen($otherBrushes) > 0) {
			
			//define it as array
			$othersArray = explode("\n", $otherBrushes);
			
			$redefined = $otherBrushes;
			if(count($othersArray)>0){
				$redefined = implode("','", $othersArray);
			}
			$otherBrushesScript = ",'".$redefined."'";
		}
		
		
		//enable mootools
		JHtml::_('behavior.framework', true, true);

		//$js = "window.addEvent('load',function(){var path=function(){var steurl='" . JURI::base() . "';var args=arguments;var result=[];for(var i=0;i<args.length;i++)result.push(args[i].replace('@',steurl+'/plugins/content/jlhighlighter/lib/scripts/'));return result};SyntaxHighlighter.autoloader.apply(null,path('applescript            @shBrushAppleScript.js','actionscript3 as3      @shBrushAS3.js','bash shell             @shBrushBash.js','coldfusion cf          @shBrushColdFusion.js','cpp c                  @shBrushCpp.js','c# c-sharp csharp      @shBrushCSharp.js','css                    @shBrushCss.js','delphi pascal          @shBrushDelphi.js','diff patch pas         @shBrushDiff.js','erl erlang             @shBrushErlang.js','groovy                 @shBrushGroovy.js','java                   @shBrushJava.js','jfx javafx             @shBrushJavaFX.js','js jscript javascript  @shBrushJScript.js','perl pl                @shBrushPerl.js','php                    @shBrushPhp.js','text plain             @shBrushPlain.js','py python              @shBrushPython.js','ruby rails ror rb      @shBrushRuby.js','sass scss              @shBrushSass.js','scala                  @shBrushScala.js','sql                    @shBrushSql.js','vb vbnet               @shBrushVb.js','xml xhtml xslt html    @shBrushXml.js'));$config SyntaxHighlighter.all();});";
		$siteURL = JURI::base();
		$js = <<<EOF
window.addEvent('load',function(){
	var siteurl = '$siteURL';
	function path(){
	  var args = arguments,result = [];
	  for(var i = 0; i < args.length; i++){
	  	result.push(args[i].replace('@', [siteurl,'plugins/content/hs_highlighter/lib/scripts/'].join('')));
	  }
	  return result;
	};
	 
	SyntaxHighlighter.autoloader.apply(null, path(
	  'applescript            @shBrushAppleScript.js',
	  'actionscript3 as3      @shBrushAS3.js',
	  'bash shell             @shBrushBash.js',
	  'coldfusion cf          @shBrushColdFusion.js',
	  'cpp c                  @shBrushCpp.js',
	  'c# c-sharp csharp      @shBrushCSharp.js',
	  'css                    @shBrushCss.js',
	  'delphi pascal          @shBrushDelphi.js',
	  'diff patch pas         @shBrushDiff.js',
	  'erl erlang             @shBrushErlang.js',
	  'groovy                 @shBrushGroovy.js',
	  'java                   @shBrushJava.js',
	  'jfx javafx             @shBrushJavaFX.js',
	  'js jscript javascript  @shBrushJScript.js',
	  'perl pl                @shBrushPerl.js',
	  'php                    @shBrushPhp.js',
	  'text plain             @shBrushPlain.js',
	  'py python              @shBrushPython.js',
	  'ruby rails ror rb      @shBrushRuby.js',
	  'sass scss              @shBrushSass.js',
	  'scala                  @shBrushScala.js',
	  'sql                    @shBrushSql.js',
	  'vb vbnet               @shBrushVb.js',
	  'xml xhtml xslt html    @shBrushXml.js'
	  $otherBrushesScript
	));
	
	$config;
	SyntaxHighlighter.all();	
});
		
		
EOF;

		$doc -> addScriptDeclaration($js);

		$this -> _load = true;

		return true;
	}

}
