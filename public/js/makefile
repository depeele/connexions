#YUI_COMP	= yuicompressor
YUI_COMP	= yui-compressor

SRCS =	json2.js connexions.js jquery.registry.js jquery.cookie.js	\
	jquery.scrollTo.js jquery.pngFix.js jquery.localNavigation.js	\
	ui.checkbox.js ui.input.js ui.tagInput.js ui.stars.js		\
	ui.notify.js ui.confirmation.js ui.validationForm.js		\
	connexions.tabs.js connexions.collapsable.js			\
	connexions.dropdownForm.js connexions.optionGroups.js		\
	connexions.itemScope.js connexions.paginator.js			\
	connexions.pane.js connexions.itemsPane.js			\
	connexions.cloudPane.js connexions.sidebar.js			\
	connexions.itemList.js	connexions.search.js 			\
	connexions.bookmarkPost.js connexions.autocomplete.js		\
	connexions.bookmark.js connexions.user.js			\
	jquery.colorhelpers.js jquery.flot.js connexions.timeline.js

SETTINGS_SRCS	= jquery.Jcrop.js 					\
		  ui.fileUploader.js					\
		  settings.avatarChooser.js				\
		  settings.accountInfo.js				\
		  settings.credential.js settings.credentials.js	\
		  settings.apiKey.js					\
		  settings.bookmarksImport.js				\
		  settings.tagsFilter.js				\
		  settings.tagsManagePane.js				\
		  settings.networkControl.js

#all: connexions.min.css bookmarks.min.css users.min.css 	\
#	urlItems.min.css inbox.min.css				\
# 	validationForm.min.css post.min.css auth.min.css 	\
#	inbox.min.css
all:	jquery-ui.min.js connexions-full.js connexions-full.min.js	\
	settings-full.js   settings-full.min.js

connexions-full.js: $(SRCS)
	cat $(SRCS) > $@

settings-full.js: $(SETTINGS_SRCS)
	cat $(SETTINGS_SRCS) > $@

jquery-ui.min.js: jquery-ui.js

clean:
	rm -f connexions-full.js connexions-full.min.js \
	      settings-full.js settings-full.min.js

#----------------------------------------------------------------------------

%.min.js : %.js
	$(YUI_COMP) -o $@ $<
