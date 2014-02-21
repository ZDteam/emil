<?php
/**
 * @version		1.0.6
 * @package		Hs - Highlighter 2014-01-21 
 * @author		Hayato Sugimoto
 * @copyright	Copyright (C) 2005 - 2014  Hayatos. All rights reserved.
 * @license		GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Editor Readmore buton
 *
 * @package     Joomla.Plugin
 * @subpackage  Editors-xtd.readmore
 * @since       1.5
 */
class plgButtonHs_highlighter extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
	 * @since       1.5
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * readmore button
	 * @return array A two element array of (imageName, textToInsert)
	 */
	public function onDisplay($name)
	{

		//load mootools
		JHtml::_('behavior.framework', true, true);
		
		$app = JFactory::getApplication();
		$doc = JFactory::getDocument();
		
		$pathBase = 'plugins/editors-xtd/hs_highlighter';
		if($app->isAdmin()){
			$pathBase = '../'.$pathBase;
		}
		
		//$doc->addScript($pathBase.'/js/hs_highlighter.js');
		$doc->addStyleSheet($pathBase.'/css/hs_highlighter.css');
		
		
		
		// button is not active in specific content components
		$getContent = $this->_subject->getContent($name);	
		
		
		//getparam
		$endtag = $this->params->get('endtag', '');
		
		
		
		//get language
		$textTitleSelectType = JText::_('PLG_EDITOR_XTD_HS_HIGHLIGHTER_TITLE_SELECT_TYPE');
		$textTitleInsertCode = JText::_('PLG_EDITOR_XTD_HS_HIGHLIGHTER_TITLE_INSERT_CODE');
		
		$textTitleHilightLine = htmlspecialchars( JText::_('PLG_EDITOR_XTD_HS_HIGHLIGHTER_TITLE_HILIGHT_LINE') );
		$textDescHilightLine=JText::_('PLG_EDITOR_XTD_HS_HIGHLIGHTER_DESC_HILIGHT_LINE');

		$textTitleFirstLine = htmlspecialchars( JText::_('PLG_EDITOR_XTD_HS_HIGHLIGHTER_TITLE_FIRST_LINE') );
		$textDescFirstLine=JText::_('PLG_EDITOR_XTD_HS_HIGHLIGHTER_DESC_FIRST_LINE');
				
		$textButtonSubmit = JText::_('PLG_EDITOR_XTD_HS_HIGHLIGHTER_BUTTON_INSERT_CODE');
		
		
		//get other brushes
		//jimport('joomla.plugin.helper');
		//jimport( 'joomla.html.parameter' );
		
		
		$contentPlugin = JPluginHelper::getPlugin('content', 'hs_highlighter');
		//$contentParams = new JParameter($contentPlugin->params);
		$contentParams = json_decode($contentPlugin->params,true);
		
		$otherBrushes = trim($contentParams['other-brushes']);
		$otherBrushesScript = '';
		
		if (strlen($otherBrushes) > 0) {
			
			$otherBrushes=trim(preg_replace('/@.*/', '',$otherBrushes));
			
			//define it as array
			$othersArray = explode("\n", $otherBrushes);
			
			$redefined = $otherBrushes;
			if(count($othersArray)>0){
				$redefined = implode("','", $othersArray);
			}
			$otherBrushesScript = ",'".$redefined."'";
		}
		
		
		
$js = <<<EOF
//$otherBrushesScript

function insertHighlighterCode(editor) {
	var bg = $('hs_fake_bg');
	var contentBox = $('hs_actual_content');
	var code = $('textarea#hs_textarea');
	
	
	if(typeOf(bg)!=='element'){
		bg = new Element('div#hs_fake_bg',{
			events:{
				'click':function(){
					bg.dispose();
					contentBox.dispose();
				}
			}
		}); 
		contentBox = new Element('div#hs_actual_content');

		//### Type ###
		var div = new Element('div.hs_high_items').inject(contentBox);	
		new Element('h3.hs_title',{
			'text':'$textTitleSelectType'
		}).inject(div);	
			
		var type =new Element('select').inject(div);
		var types = ['xml xhtml xslt html','css','javascript', 'php', 'text plain',
		'actionscript','bash shell','coldfusion','cpp','c#','delphi pascal','diff patch pas','erl erlang','groovy',
		'java','jfx javafx','perl','python','ruby rails','sass scss','scala','sql','vb vbnet'
		$otherBrushesScript];
		
		types.each(function(t){
			var name = t.split(' ')[0];
			new Element('option',{
				'text':t,
				'value':name
			}).inject(type)
		});
		
		//### Code ###
		var div = new Element('div.hs_high_items').inject(contentBox);	
		new Element('h3.hs_title',{
			'text':'$textTitleInsertCode'
		}).inject(div);		
		var code = new Element('textarea#hs_textarea').inject(div);
		
		
		//### Highlight Lines #####
		var div = new Element('div.hs_high_items').inject(contentBox);	
		new Element('h3.hs_title',{
			'text':'$textTitleHilightLine'
		}).inject(div);		
		new Element('div.hs_desc',{
			'text':"$textDescHilightLine"
		}).inject(div);		
		
		var lines = new Element('input',{
			'type':'text'
		}).inject(div);
				
		
		//### First Line ###
		var div = new Element('div.hs_high_items').inject(contentBox);	
		new Element('h3.hs_title',{
			'text':'$textTitleFirstLine'
		}).inject(div);		
		new Element('div.hs_desc',{
			'text':"$textDescFirstLine"
		}).inject(div);		
		
		var firstLine = new Element('input',{
			'type':'text'
		}).inject(div);		
		
		
		//### Button ###
		var div = new Element('div.hs_high_items').inject(contentBox);	
		new Element('a.btn.hs_btn_insert',{
			'html':['<i class="icon-arrow-right blank"></i>', '<span class="inserttext">', '$textButtonSubmit', '</span>'].join(''),
			href:'javascript:void(0)',
			events:{
				'click':function(){
					var t = type.get('value'),
						c = code.get('value'),
						l = lines.get('value').trim(),
						f = firstLine.get('value').trim().toInt();
					
					//check something value is inserted
					if(c.length>0){
						
						//Highlight lines
						if(l.length>0){
							l = ['; highlight: [',l,']'].join('');
						}else{
							l = '';
						}
						
						
						
						//first line						
						if(f>0){
							f= ['; first-line:', f].join('');
						}else{
							f='';
						}
						
						//care html special chars
						// => not only html but all code replace html special chars?
						//if(t==='xml'){
							c = c.replace(/&/g, '&amp;');
							c = c.replace(/\"/g, '&quot;');
							c = c.replace(/\'/g, '&#039;');
							c = c.replace(/</g, '&lt;');
							c = c.replace(/>/g, '&gt;');
						//}
						
						
						//add pre tag
						c = [['<pre class="brush:', t, l, f, '">'].join(''), c, '</pre>'].join('');
						
						//change
						switch('$endtag'){
							case 'p':
								c =[c,'<p></p>'].join('');
								break;
							case 'br':
								c =[c,'<br />'].join('');
								break;
						}
						
						
						jInsertEditorText(c, editor);
					}
					contentBox.dispose();
					bg.dispose();
				}
			}
		}).inject(div);
	}
	
	// pop up effect
	new Fx.Scroll(window).toTop();
	
	var w = window.getSize();
	
	bg.setStyles({
		'opacity':0.6,
		'width':w.x,
		'height':w.y
	});
	
	contentBox.setStyles({
		'left':(w.x - 660)/2,
		'top':30
	});
	
	bg.inject(document.body);
	
	contentBox.inject(document.body);
	
}
EOF;
		$doc->addScriptDeclaration($js);

		$button = new JObject;
		$button->modal = false;
		$button->class = 'btn';
		$button->id = 'hs-highlighter-button';
		//$button->onclick = 'insertHighlighterCode(\''.$name.'\');return false;';
		$button->onclick = 'insertHighlighterCode(\''.$name.'\');return false;';
		$button->text = JText::_('PLG_EDITOR_XTD_HS_HIGHLIGHTER_BUTTON_ADD_CODE');
		$button->name = 'arrow-down blank';
		// TODO: The button writer needs to take into account the javascript directive
		//$button->link', 'javascript:void(0)');
		$button->link = 'javascript:void(0)';


		return $button;
	}
}
