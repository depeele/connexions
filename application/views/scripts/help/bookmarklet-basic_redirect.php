javascript:(function(){location.href='<?= $this->baseUrl('post') ?>?url='+encodeURIComponent(location.href)+'&name='+encodeURIComponent(document.title)+'&noNav&closeAction=back';}())
