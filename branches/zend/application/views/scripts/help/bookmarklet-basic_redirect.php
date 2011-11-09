javascript:(function(){location.href='<?= $this->serverUrl('/post') ?>?url='+encodeURIComponent(location.href)+'&name='+encodeURIComponent(document.title)+'&noNav&closeAction=back';}())
