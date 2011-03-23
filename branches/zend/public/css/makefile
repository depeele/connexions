#
# NOTE: This requires sass >= 3.0
#
#YUI_COMP	= yuicompressor
YUI_COMP	= yui-compressor


all: connexions.min.css bookmarks.min.css users.min.css 	\
	bookmark-users.min.css					\
	ui-form.min.css urlItems.min.css inbox.min.css		\
 	validationForm.min.css post.min.css auth.min.css 	\
	ribbon-form.min.css url-choose.min.css			\
	search.min.css searchResults.min.css inbox.min.css	\
	settings.min.css sprites.min.css ie.min.css ie8.min.css

#all: connexions.css bookmarks.css users.css urlItems.css inbox.css	\
# 	validationForm.css post.css auth.css inbox.css

connexions.css: main.scss		\
    		mixins.scss		\
		colors.scss		\
		sizes.scss		\
		libraries.scss		\
		template.scss		\
		grids.scss		\
		content.scss		\
		sprites.scss		\
		urls.scss		\
		tabs.scss		\
		ui-form.scss		\
		ui.confirmation.scss	\
		tagCloud.scss		\
		itemScope.scss		\
		pagination.scss		\
		displayOptions.scss	\
		ui.notify.scss		\
		validationForm.scss	\
		uiStars.scss		\
		post.scss		\
		ribbon-form.scss
	sass -t compact main.scss:connexions.css

items.css: mixins.scss items.scss colors.scss sizes.scss
	sass -t compact items.scss:items.css

bookmarks.css: mixins.scss bookmarks.scss items.scss bookmark.scss \
		uiStars.scss colors.scss sizes.scss
	sass -t compact bookmarks.scss:bookmarks.css

users.css: mixins.scss users.scss items.scss user.scss colors.scss sizes.scss
	sass -t compact users.scss:users.css

bookmark-users.css: mixins.scss users.scss user.scss \
    			bookmark-users.scss colors.scss sizes.scss
	sass -t compact bookmark-users.scss:bookmark-users.css

urlItems.css: mixins.scss urlItems.scss bookmark.scss \
		uiStars.scss colors.scss sizes.scss urls.scss
	sass -t compact urlItems.scss:urlItems.css

inbox.css: mixins.scss inbox.scss colors.scss sizes.scss
	sass -t compact inbox.scss:inbox.css

validationForm.css: mixins.scss validationForm.scss colors.scss uiStars.scss
	sass -t compact validationForm.scss:validationForm.css

post.css: mixins.scss post.scss colors.scss ribbon-form.scss
	sass -t compact post.scss:post.css

auth.css: mixins.scss auth.scss colors.scss ribbon-form.scss
	sass -t compact auth.scss:auth.css

url-choose.css: mixins.scss url-choose.scss colors.scss ribbon-form.scss
	sass -t compact url-choose.scss:url-choose.css

search.css: mixins.scss search.scss colors.scss ribbon-form.scss
	sass -t compact search.scss:search.css

searchResults.css: mixins.scss searchResults.scss colors.scss
	sass -t compact searchResults.scss:searchResults.css

settings.css: settings.scss sizes.scss colors.scss mixins.scss urls.scss \
		sprites.scss jquery_Jcrop.scss ui-fileUploader.scss users.scss
	sass -t compact settings.scss:settings.css

ui.confirmation.css: ui.confirmation.scss sizes.scss colors.scss mixins.scss
	sass -t compact ui.confirmation.scss:ui.confirmation.css

sprites.scss:	urls.scss

ie.scss:	sizes.scss colors.scss

ie8.scss:	sizes.scss colors.scss

users.scss:	sizes.scss colors.scss mixins.scss items.scss user.scss

#----------------------------------------------------------------------------
clean:
	rm -f connexions.css items.css bookmarks.css users.css \
	      bookmark-users.css urlItems.css inbox.css validationForm.css \
	      post.css auth.css url-chooser.css search.css searchResults.css \
	      ui.confirmation.css settings.css sprites.css ie.css ie8.css \
	      connexions.min.css items.min.css bookmarks.min.css users.min.css \
	      bookmark-users.min.css urlItems.min.css inbox.min.css \
	      validationForm.min.css post.min.css auth.min.css \
	      url-chooser.min.css search.min.css searchResults.min.css \
	      ui.confirmation.min.css settings.min.css sprites.min.css \
	      ie.min.css ie8.min.css

%.css : %.scss
	sass -t compact $<:$@

%.min.css : %.css
	$(YUI_COMP) $< -o $@
