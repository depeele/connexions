YUI_COMP	= yuicompressor

SRCS =	connexions.js jquery.registry.js jquery.cookie.js 		\
	jquery.pngFix.js						\
	ui.checkbox.js ui.input.js ui.stars.js ui.notify.js		\
	connexions.dropdownForm.js connexions.optionGroups.js		\
	connexions.itemScope.js connexions.paginator.js			\
	connexions.bookmark.js connexions.bookmarkList.js		\
	connexions.pane.js connexions.bookmarksPane.js			\
	connexions.cloudPane.js connexions.sidebar.js			\
	connexions.search.js connexions.bookmarkPost.js			\
	connexions.autocomplete.js

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
