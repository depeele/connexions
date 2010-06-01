YUI_COMP	= yuicompressor

MINS =	connexions.min.js jquery.class.min.js jquery.cookie.min.js 	\
	jquery.pngFix.min.js						\
	ui.button.min.js ui.checkbox.min.js ui.dropdownForm.min.js 	\
	ui.input.min.js ui.optionGroups.min.js ui.stars.min.js		\
	ui.bookmark.min.js ui.bookmarkList.min.js			\
	ui.pane.min.js ui.bookmarksPane.min.js ui.cloudPane.min.js	\
	ui.paginator.min.js ui.notify.min.js

#all: connexions.min.css bookmarks.min.css users.min.css 	\
#	urlItems.min.css inbox.min.css				\
# 	validationForm.min.css post.min.css auth.min.css 	\
#	inbox.min.css
all:	$(MINS) connexions-full.js

connexions-full.js: $(MINS)
	cat $(MINS) > $@

#----------------------------------------------------------------------------

%.min.js : %.js
	$(YUI_COMP) $<
