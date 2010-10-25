<?php
/**
 * BBCodeManager
 *
 * This file is part of BBCodeManager, a bbcode and html parser for MODx Revolution.
 *
 * BBCodeManager is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * BBCodeManager is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * BBCodeManager; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package bbcodemanager
 */
/**
 * Main file class for BBCodeManager
 *
 * @name BBCodeManager
 * @author Stephane Boulard
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @package bbcodemanager
 */
class BBCodeManager {
    /**
     * @access public
     * @var modX A reference to the modX object.
     */
    public $modx = null;
    /**
     * @access public
     * @var array A collection of properties to adjust BBCodeManager behaviour.
     */
    public $config = array();

    /**
     * The BBCodeManager Constructor.
     *
     * Create a new BBCodeManager object.
     *
     * @param modX &$modx A reference to the modX object.
     * @param array $config A collection of properties that modify BBCodeManager
     * behaviour.
     * @return BBCodeManager A unique BBCodeManager instance.
     */
    function __construct(modX &$modx,array $config = array()) {
        $this->modx =& $modx;

        $core = $this->modx->getOption('core_path').'components/bbcodemanager/';
        $assets_url = $this->modx->getOption('assets_url').'components/bbcodemanager/';
        $assets_path = $this->modx->getOption('assets_path').'components/bbcodemanager/';
		
		//The following snippets parameters are draft or our past conversation
        $this->config = array_merge(array(
            'core_path' => $core,
            'model_path' => $core.'model/',
            'libs_path' => $core.'libs/',         
        ),$config);
    
		// if ($this->modx->lexicon) {
            // $this->modx->lexicon->load('bbcodemanager:default');
        // }

        /* load debugging settings */
		//I have not sorted out yet how to work with the debugger - Would be good to proveide a table output of settings
        if ($this->modx->getOption('debug',$this->config,false)) {
            error_reporting(E_ALL); ini_set('display_errors',true);
            $this->modx->setLogTarget('HTML');
            $this->modx->setLogLevel(MODX_LOG_LEVEL_ERROR);
			
			//We need to change here 
            $debugUser = $this->config['debugUser'] == '' ? $this->modx->user->get('username') : 'anonymous';
            $user = $this->modx->getObject('modUser',array('username' => $debugUser));
            if ($user == null) {
                $this->modx->user->set('id',$this->modx->getOption('debugUserId',$this->config,1));
                $this->modx->user->set('username',$debugUser);
            } else {
                $this->modx->user = $user;
            }
        }
    }

    /**
     * Initializes BBCodeManager based on a specific context.
     *
     * @access public
     * @param string $ctx The context to initialize in.
     * @return string The processed content.
     */
    public function parse($content) {
        $output = '';
      
		/* Load the Parser Class and declare a new instance of it */
		if (!$this->modx->loadClass('nbbc',$this->config['libs_path'].'bbcodeparser/',true,true)) {
			return 'Could not load the BBCode Parser class.';			
		}		
		
		$BBCode = new BBCode();		
		
		//Reset all default rules and add only the desired one
		//@TODO allow them programatically
		$BBCode->ClearRules();
		$BBCode->ClearSmileys();
		$BBCode->SetAllowAmpersand(true);
		
		$BBCode->SetSmileyDir('/assets/images/emoticon');
		$BBCode->SetSmileyURL ('/assets/images/emoticon/');
		
		$BBCode->AddSmiley(":)", "smile.gif");		
		$BBCode->AddSmiley(":roll:", "icon_rolleyes.gif");		
		$BBCode->AddSmiley(":D", "teeth.gif");		
		$BBCode->AddSmiley(":transpi:", "transpi.gif");		
		$BBCode->AddSmiley(":devil:", "devil.gif");		
		$BBCode->AddSmiley(":lol:", "laugh.gif");		
				
		$BBCode->AddRule('b', Array(
			'simple_start' => "<b>",
			'simple_end' => "</b>",
			'class' => 'inline',
			'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
			'plain_start' => "<b>",
			'plain_end' => "</b>",
		));
		
		$BBCode->AddRule('s', Array(
			'simple_start' => "<strike>",
			'simple_end' => "</strike>",
			'class' => 'inline',
			'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
			'plain_start' => "<i>",
			'plain_end' => "</i>",
		));

		$BBCode->AddRule('i', Array(
			'simple_start' => "<i>",
			'simple_end' => "</i>",
			'class' => 'inline',
			'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
			'plain_start' => "<i>",
			'plain_end' => "</i>",
		));
		
		$BBCode->AddRule('quote', Array(
			'mode' => BBCODE_MODE_CALLBACK,
			'method' => Array($this, 'doQuote'),
			'allow_in' => Array('listitem', 'block', 'columns'),
			'before_tag' => "sns",
			'after_tag' => "sns",
			'before_endtag' => "sns",
			'after_endtag' => "sns",
			'plain_start' => "\n<b>Quote:</b>\n",
			'plain_end' => "\n",
		));
		
		$BBCode->AddRule('url', Array(
			'mode' => BBCODE_MODE_CALLBACK,
			'method' => Array($this, 'doURL'),
			'class' => 'link',
			'allow_in' => Array('listitem', 'block', 'columns', 'inline'),
			'content' => BBCODE_REQUIRED,
			'plain_start' => "<a href=\"{\$link}\">",
			'plain_end' => "</a>",
			'plain_content' => Array('_content', '_default'),
			'plain_link' => Array('_default', '_content'),
		));
		
		$BBCode->AddRule('list', Array(
			'mode' => BBCODE_MODE_CALLBACK,
			'method' => Array($this, 'doList'),
			'class' => 'list',
			'allow_in' => Array('listitem', 'block', 'columns'),
			'before_tag' => "sns",
			'after_tag' => "sns",
			'before_endtag' => "sns",
			'after_endtag' => "sns",
			'plain_start' => "\n",
			'plain_end' => "\n",
		));
		
		$BBCode->AddRule('code', Array(
			'mode' => BBCODE_MODE_CALLBACK,
			'default' => 'html',
			'method' => Array($this, 'doCode'),			
			'class' => 'code',
			'allow_in' => Array('listitem', 'block', 'columns'),
			'content' => BBCODE_VERBATIM,
		));
		
		$BBCode->AddRule('*', Array(
			'simple_start' => "<li>",
			'simple_end' => "</li>\n",
			'class' => 'listitem',
			'allow_in' => Array('list'),
			'end_tag' => BBCODE_OPTIONAL,
			'before_tag' => "s",
			'after_tag' => "s",
			'before_endtag' => "sns",
			'after_endtag' => "sns",
			'plain_start' => "\n * ",
			'plain_end' => "\n",
		));			
		
		/* list item compatibilty with SMF */
		$BBCode->AddRule('li', Array(
			'simple_start' => "<li>",
			'simple_end' => "</li>\n",
			'class' => 'listitem',
			'allow_in' => Array('list'),
			'end_tag' => BBCODE_OPTIONAL,
			'before_tag' => "s",
			'after_tag' => "s",
			'before_endtag' => "sns",
			'after_endtag' => "sns",
			'plain_start' => "<li>",
			'plain_end' => "</li>^n",
		));			
		
		$output = $BBCode->Parse($content);
		
		return $output;
	}
				
	public function doQuote($BBCode, $action, $name, $default, $params, $content) {
		
		if ($action == BBCODE_CHECK) return true;
		
		if (isset($params['name'])) {
			$title = htmlspecialchars(trim($params['name'])) . " wrote";
			if (isset($params['date']))
				$title .= " on " . htmlspecialchars(trim($params['date']));
			$title .= ":";
			if (isset($params['url'])) {
				$url = trim($params['url']);
				if ($BBCode->IsValidURL($url))
					$title = "<a href=\"" . htmlspecialchars($params['url']) . "\">" . $title . "</a>";
			}
		}
		else if (!is_string($default))
			$title = "Quote";
		else $title = htmlspecialchars(trim($default)) . " wrote:";
		return "\n<blockquote>\n<p class=\"title\">"
			. $title . "</p>\n<div class=\"body\">"
			. $content . "</div>\n</blockquote>\n";
	}
		
	public function doCode($BBCode, $action, $name, $default, $params, $content) {
		
		if ($action == BBCODE_CHECK) return true;
		
		if (isset($params['class'])) {
			$class = htmlspecialchars(trim($params['class']));
		}
		else {
			$class = 'html';
		}
		//Encode MODx tags
		return '<pre class="'.$class.'">'.$content.'</pre>';
	}

	public function doURL($BBCode, $action, $name, $default, $params, $content) {
		// We can't check this with BBCODE_CHECK because we may have no URL before the content has been processed.
		if ($action == BBCODE_CHECK) return true;

		$url = is_string($default) ? $default : $BBCode->UnHTMLEncode(strip_tags($content));
		if ($BBCode->IsValidURL($url)) {
			if ($BBCode->url_targetable !== false && isset($params['target']))
				$target = " target=\"" . htmlspecialchars($params['target']) . "\"";
			else $target = "";
			if ($BBCode->url_target !== false)
				if (!($BBCode->url_targetable == 'override' && isset($params['target'])))
					$target = " target=\"" . htmlspecialchars($BBCode->url_target) . "\"";
			return '<a href="' . htmlspecialchars($url) . '" ' . $target . '>' . $content . '</a>';
		}
		else return htmlspecialchars($params['_tag']) . $content . htmlspecialchars($params['_endtag']);
	}	
		
	public function DoList($bbcode, $action, $name, $default, $params, $content) {

		// Allowed list styles, striaght from the CSS 2.1 spec.  The only prohibited list style is that with image-based markers, which often slows down web sites.
		$list_styles = Array(
			'1' => 'decimal',
			'01' => 'decimal-leading-zero',
			'i' => 'lower-roman',
			'I' => 'upper-roman',
			'a' => 'lower-alpha',
			'A' => 'upper-alpha',
		);
		$ci_list_styles = Array(
			'circle' => 'circle',
			'disc' => 'disc',
			'square' => 'square',
			'greek' => 'lower-greek',
			'armenian' => 'armenian',
			'georgian' => 'georgian',
		);
		$ul_types = Array(
			'circle' => 'circle',
			'disc' => 'disc',
			'square' => 'square',
		);

		$default = trim($default);

		if ($action == BBCODE_CHECK) {
			if (!is_string($default) || strlen($default) == "") return true;
			else if (isset($list_styles[$default])) return true;
			else if (isset($ci_list_styles[strtolower($default)])) return true;
			else return false;
		}

		// Choose a list element (<ul> or <ol>) and a style.
		if (!is_string($default) || strlen($default) == "") {
			$elem = 'ul';
			$type = '';
		}
		else if ($default == '1') {
			$elem = 'ol';
			$type = '';
		}
		else if (isset($list_styles[$default])) {
			$elem = 'ol';
			$type = $list_styles[$default];
		}
		else {
			$default = strtolower($default);
			if (isset($ul_types[$default])) {
				$elem = 'ul';
				$type = $ul_types[$default];
			}
			else if (isset($ci_list_styles[$default])) {
				$elem = 'ol';
				$type = $ci_list_styles[$default];
			}
		}

		// Generate the HTML for it.
		if (strlen($type))
			return "\n<$elem style=\"list-style-type:$type\">\n$content</$elem>\n";
		else return "\n<$elem>\n$content</$elem>\n";
	}
	
	/**
     * limitQuotes 
     *
     * Modified from http://www.nickm.org/projects/phpBB/nested_quote_limit_mod.txt
     *
     * @access private
     * @param string - $data The content to modify
     * @return string - The processed content
     */
	public function limitQuote($data)
	{
		/* Discuss plugin compatibility */
		if(is_object($data)){
			$text = $obj->get('message');
			$returnObj = true;
		} else {
			$text = $data;
			$returnObj = false;
		}

		$depthQuoteLimit = $this->modx->getOption('bbcodesuite.depth_quote_limit',$scriptProperties, 2);
		
		$num_quotes = preg_match_all("#\[quote(.*)#isU", $text, $matches);
		
		if ($num_quotes > $depthQuoteLimit) {
			// Define default values.
			$curr_pos = 1;
			$level = 0;
			while (isset ($curr_pos) && ($curr_pos < strlen($text))) {
				$curr_pos_cmp[0] = strpos($text, "[quote", $curr_pos);
				$curr_pos_cmp[1] = strpos($text, "[quote ", $curr_pos);
				$curr_pos_cmp[2] = strpos($text, "[/quote]", $curr_pos);
				
				// Filter out all the empty values to determine if we should
				// continue or bail. Also makes the array play nice with min ().
				$curr_pos_cmp = array_filter($curr_pos_cmp, 'self::returnInts');
				
				if (!empty ($curr_pos_cmp)) {
					// Find the first tag we should deal with.
					$curr_pos = min($curr_pos_cmp);
					
					// Set everything to false so things won't get messy with the loop.
					$quote_open = null;
					$quote_open_user = null;
					$quote_close = null;
					
					// Find out which type of tag we're dealing with.
					switch ($curr_pos) {
						case $curr_pos_cmp[0]:
							$quote_open = true;
							break;
						case $curr_pos_cmp[1]:
							$quote_open_user = true;
							break;
						case $curr_pos_cmp[2]:
							$quote_close = true;
							break;
					}
				} else {
					// $curr_pos_cmp array is empty so there's nothing left to do.
					$curr_pos = null;
				}
				
				// If we have a tag we'll start.
				if (isset ($curr_pos)) {
					// If we're in front of an opening quote tag at the maxmimum
					// level, we'll insert a [quote_kill] tag.
					if ((isset ($quote_open) || isset ($quote_open_user)) && $level == $depthQuoteLimit) {
						$text = substr_replace($text, "[quote_kill]", $curr_pos, 0);
						$curr_pos += strlen("[quote_kill]");
					}
					
					// Depending on the type of tag we have, increase or decrease
					// the level count and set the cursor ahead of the tag.
					if (isset ($quote_open)) {
						$level++;
						
						$curr_pos += strlen("[quote]");
					} else if (isset ($quote_open_user)) {
						$level++;
						
						// Find the ending of the quote tag for usernames.
						$end_bracket_pos = strpos ($text, ']', $curr_pos);
						$curr_pos += (($end_bracket_pos + 1) - $curr_pos);
					} else if (isset($quote_close)) {
						$level--;						
						$curr_pos += strlen("[/quote]");
					}
					
					// Now we're positioned after a quote closing quote tag at
					// the maximum level, so we'll insert an ending [/quote_kill] tag
					if (isset($quote_close) && $level == $depthQuoteLimit) {
						$text = substr_replace($text, "[/quote_kill]", $curr_pos, 0);
						$curr_pos += strlen("[/quote_kill]");
					}					
				}
				// We've now traversed all of the text and have inserted [quote_kill]
				// tags as needed. Now all we delete the tags and everything inside.
				$text = preg_replace("/(\s*)\[quote_kill\](.*?)\[\/quote_kill\](\s*)/si", ' ', $text);
			}			
		}
		if($returnObj){
			$obj->set('message', $text);
		} else {
			return $text;
		}
		return;
	}
		
	/**
     * returnInts 
     *
     * @access private
     * @param int - $var  Tne variable to verify
     * @return $var if it's int, null if not
     */
	private function returnInts($var) {
		return (is_int($var)) ? $var : null;
	}
}