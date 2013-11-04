<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>PHP Console usage examples</title>
	<link rel="stylesheet" href="../styles.css" />
	<script src="../jquery-2.0.3.min.js"></script>
	<script type="text/javascript">
		$(function () {
			$('#testButton').click(function () {
				var link = document.createElement('a');
				link.setAttribute('href', 'editor://open/?file='
					+ encodeURIComponent($('#editorTestPath').val().trim())
					+ '&line='
					+ encodeURIComponent($('#editorTestLine').val().trim())
				);
				link.click();
				return false;
			});
		})
		;
	</script>
</head>
<body>

This is a test tool for <a href="https://github.com/barbushin/php-console/wiki/Jump-to-File">Jump to File</a> feature.

<form class="pure-form">
	<fieldset>
		<input type="text" placeholder="Path to some local PHP file" id="editorTestPath" style="width: 500px;" />
		<input type="text" placeholder="Line" size="4" id="editorTestLine" />
		<button class="pure-button pure-button-primary" id="testButton">Open in editor</button>
	</fieldset>
</form>

</body>
</html>
