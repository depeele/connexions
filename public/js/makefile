#YUI_COMP	= yuicompressor
YUI_COMP	= yui-compressor

SRCS =	connexions.js jquery.registry.js jquery.cookie.js 		\
	jquery.pngFix.js						\
	ui.checkbox.js ui.input.js ui.stars.js ui.notify.js		\
	ui.validationForm.js						\
	connexions.collapsable.js					\
	connexions.dropdownForm.js connexions.optionGroups.js		\
	connexions.itemScope.js connexions.paginator.js			\
	connexions.pane.js connexions.itemsPane.js			\
	connexions.cloudPane.js connexions.sidebar.js			\
	connexions.itemList.js	connexions.search.js 			\
	connexions.bookmarkPost.js connexions.autocomplete.js		\
	connexions.bookmark.js connexions.user.js

SETTINGS_SRCS	= jquery.Jcrop.js ui.fileUploader.js			\
		  settings.avatarChooser.js				\
		  settings.accountInfo.js				\
		  settings.credential.js settings.credentials.js	\
		  settings.apiKey.js

#all: connexions.min.css bookmarks.min.css users.min.css 	\
#	urlItems.min.css inbox.min.css				\
# 	validationForm.min.css post.min.css auth.min.css 	\
#	inbox.min.css
all:	connexions-full.js connexions-full.min.js	\
	settings-full.js   settings-full.min.js

connexions-full.js: $(SRCS)
	cat $(SRCS) > $@

settings-full.js: $(SETTINGS_SRCS)
	cat $(SETTINGS_SRCS) > $@

clean:
	rm -f connexions-full.js connexions-full.min.js \
	      settings-full.js settings-full.min.js

#----------------------------------------------------------------------------

%.min.js : %.js
	$(YUI_COMP) $< -o $@
