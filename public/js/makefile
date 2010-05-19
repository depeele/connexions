YUI_COMP	= yuicompressor


#all: connexions.min.css bookmarks.min.css users.min.css 	\
#	urlItems.min.css inbox.min.css				\
# 	validationForm.min.css post.min.css auth.min.css 	\
#	inbox.min.css
all:	connexions.min.js jquery.class.min.js jquery.cookie.min.js 	\
	ui.bookmark.min.js ui.bookmarkList.min.js			\
	ui.button.min.js ui.checkbox.min.js ui.dropdownForm.min.js 	\
	ui.input.min.js ui.optionGroups.min.js ui.stars.min.js

#----------------------------------------------------------------------------

%.min.js : %.js
	$(YUI_COMP) $<
