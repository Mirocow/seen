<?php if (!defined('YII_DEBUG') || YII_DEBUG == false): ?>
<script type="text/javascript">
	var _paq = _paq || [];
	_paq.push(["setCookieDomain", "*.seenapp.com"]);
	_paq.push(['trackPageView']);
	_paq.push(['enableLinkTracking']);
	(function() {
	var u=(("https:" == document.location.protocol) ? "https" : "http") + "://stats.visualappeal.de/";
	_paq.push(['setTrackerUrl', u+'piwik.php']);
	_paq.push(['setSiteId', 20]);
	var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript';
	g.defer=true; g.async=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
	})();
</script>
<noscript><p><img src="http://stats.visualappeal.de/piwik.php?idsite=20" style="border:0;" alt="" /></p></noscript>
<?php endif; ?>