<?php

/**
 * Browser for PHP Console features examples in ./features directory
 *
 * You will need to install Google Chrome extension "PHP Console"
 * https://chrome.google.com/webstore/detail/nfhmhhlpfleoednkpnnnkolmclajemef
 *
 * @see http://github.com/barbushin/php-console
 * @version 3.0
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @license http://opensource.org/licenses/BSD-3-Clause "BSD 3-Clause License"
 * @copyright © Sergey Barbushin, 2013. Some rights reserved.
 */

// List of scripts from ./features directory
$features = array(
	'debug_vars' => 'Debug vars',
	'handle_errors' => 'Handle errors and exceptions',
	'handle_on_redirect' => 'Handle messages on redirect',
	'handle_javascript_errors' => 'Handle JavaScript errors',
	'protect_by_password' => 'Protect by password',
	'eval_terminal' => 'PHP code remote execution',
	'highload_optimization' => 'Highload optimization',
	'complex_usage_example' => 'Complex usage example',
);

$utils = array(
	'build_phar' => 'Build PHAR',
	'test_jump_to_file' => 'Test Jump to File',
	'detect_headers_limit' => 'Detect server headers limit',
);

// Highlight & print feature script source code
if(isset($_GET['highlight']) && isset($features[$_GET['highlight']])) {
	highlight_file(__DIR__ . '/features/' . $_GET['highlight'] . '.php');
	exit;
}

?>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>PHP Console usage examples</title>
	<link rel="stylesheet" href="styles.css" />
	<script src="jquery-2.0.3.min.js"></script>
	<script>
		$(function () {

			function initMenuItems(items, id, showSource) {
				for(var alias in items) {
					$('#' + id).append($('<a>', {href: '#' + alias, text: items[alias], class: 'link', id: alias})
						.click(function () {
							var uri = id + '/' + this.id + '.php';

							$('#content').hide();
							$('#outputTitle').text(this.text);
							$('#sourceCodeLink').text('./' + uri).attr('href', uri);
							$('a').removeClass('active');
							$(this).addClass('active');
							console.clear();

							if(showSource) {
								$('#sourceCode').html('').load('?highlight=' + this.id);
								$('#sourceCode').show();
							}
							else {
								$('#sourceCode').hide();
							}

							$('#outputIFrame').height(0).attr('src', uri)
								.load(function () {
									$('#outputIFrame').contents().find('body').append($('<link rel="stylesheet" href="../pure-nr-min.css" />'));
									$('#content').show();
									$(this).height(this.contentWindow.document.body.offsetHeight);
								});
						}));
				}
			}

			initMenuItems(<?= json_encode($features) ?>, 'features', true);
			initMenuItems(<?= json_encode($utils) ?>, 'utils');

			if(window.location.hash) {
				$('#' + window.location.hash.substr(1)).trigger('click');
			}
		});
	</script>
</head>
<body>

<h1 align="center">PHP Console Features examples & Utils</h1>

<? if(!isset($_COOKIE['php-console-client'])) { ?>
	<span class="warning" align="center">
	Google Chrome extension
	<a href="https://chrome.google.com/webstore/detail/php-console/nfhmhhlpfleoednkpnnnkolmclajemef" target="_blank">PHP Console</a>
	must be installed.
</span>
<? } ?>


<div class="pure-g" style="width: 100%; padding: 20px;">

	<div class="pure-u-1" style="width:250px">
		<h2>Features</h2>

		<div id="features"></div>

		<h2>Utils</h2>

		<div id="utils"></div>
	</div>


	<div class="pure-u-1" id="content" style="width:1000px; display: none;">
		<h2 id="outputTitle"></h2>
		<iframe height="0" allowtransparency="true" scrolling="no" id="outputIFrame" class="code"></iframe>

		<p>
			<a id="sourceCodeLink" target="_blank"></a>
		</p>

		<div id="sourceCode" class="code" style="display: none; overflow: auto;"></div>
	</div>

</div>


</body>
</html>
