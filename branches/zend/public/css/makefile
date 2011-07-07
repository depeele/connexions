#
# NOTE: This requires sass >= 3.0
#
#YUI_COMP	= yuicompressor
YUI_COMP	= yui-compressor


all: connexions.min.css post.min.css auth.min.css 		\
	url-choose.min.css search.min.css settings.min.css 	\
	help.min.css						\
	ie.min.css ie8.min.css 					\
	themes/connexions/jquery-ui.min.css

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
		ribbon-form.scss	\
		ui.confirmation.scss	\
		ui.tagInput.scss	\
		timeline.scss		\
		tagCloud.scss		\
		itemScope.scss		\
		pagination.scss		\
		displayOptions.scss	\
		ui.notify.scss		\
		validationForm.scss	\
		uiStars.scss		\
		post.scss		\
		items.scss		\
		bookmark.scss		\
		user.scss
	sass -t compact main.scss:connexions.css

post.css: mixins.scss post.scss colors.scss ribbon-form.scss
	sass -t compact post.scss:post.css

auth.css: mixins.scss auth.scss colors.scss ribbon-form.scss
	sass -t compact auth.scss:auth.css

url-choose.css: url-choose.scss colors.scss mixins.scss ribbon-form.scss
	sass -t compact url-choose.scss:url-choose.css

search.css: search.scss sizes.scss colors.scss mixins.scss ribbon-form.scss \
		searchResults.scss
	sass -t compact search.scss:search.css

settings.css: settings.scss sizes.scss colors.scss mixins.scss urls.scss \
		sprites.scss jquery_Jcrop.scss ui-fileUploader.scss users.scss
	sass -t compact settings.scss:settings.css

help.css: help.scss sizes.scss colors.scss mixins.scss urls.scss \
		sprites.scss
	sass -t compact help.scss:help.css

ie.scss:	sizes.scss ie-corner.scss

ie8.scss:	sizes.scss ie-corner.scss

ie-corner.scss:	urls.scss

#----------------------------------------------------------------------------
clean:
	rm -f connexions*.css post*.css auth*.css	\
	      url-choose*.css search*.css settings*.css \
	      help*.css 				\
	      ie*.css 					\
	      themes/connexions/jquery-ui.min.css

%.css : %.scss
	sass -t compact $<:$@

%.min.css : %.css
	$(YUI_COMP) $< -o $@
