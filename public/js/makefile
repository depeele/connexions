YUI_COMP	= yuicompressor

SRCS =	connexions.js jquery.class.js jquery.cookie.js jquery.pngFix.js	\
	ui.checkbox.js ui.dropdownForm.js ui.input.js 			\
	ui.optionGroups.js ui.stars.js					\
	ui.bookmark.js ui.bookmarkList.js				\
	ui.pane.js ui.bookmarksPane.js ui.cloudPane.js			\
	ui.paginator.js ui.notify.js ui.sidebar.js ui.itemScope.js	\
	ui.search.js

#all: connexions.min.css bookmarks.min.css users.min.css 	\
#	urlItems.min.css inbox.min.css				\
# 	validationForm.min.css post.min.css auth.min.css 	\
#	inbox.min.css
all:	$(MINS) connexions-full.js connexions-full.min.js

connexions-full.js: $(SRCS)
	cat $(SRCS) > $@

#----------------------------------------------------------------------------

%.min.js : %.js
	$(YUI_COMP) $<
