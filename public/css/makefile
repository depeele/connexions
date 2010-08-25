#
# NOTE: This requires sass >= 3.0
#
YUI_COMP	= yuicompressor


all: connexions.min.css bookmarks.min.css users.min.css 	\
	bookmark-users.min.css					\
	ui-form.min.css urlItems.min.css inbox.min.css		\
 	validationForm.min.css post.min.css auth.min.css 	\
	ribbon-form.min.css url-choose.min.css			\
	inbox.min.css ie.min.css ie8.min.css

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
		tabs.scss		\
		ui-form.scss		\
		tagCloud.scss		\
		itemScope.scss		\
		pagination.scss		\
		displayOptions.scss	\
		ui.notify.scss		\
		validationForm.scss	\
		post.scss		\
		ribbon-form.scss
	sass -t compact main.scss:connexions.css

items.css: mixins.scss items.scss colors.scss sizes.scss
	sass -t compact items.scss:items.css

bookmarks.css: mixins.scss bookmarks.scss bookmark.scss \
		uiStars.scss colors.scss sizes.scss
	sass -t compact bookmarks.scss:bookmarks.css

users.css: mixins.scss users.scss user.scss colors.scss sizes.scss
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

sprites.scss:	urls.scss

ie.scss:	sizes.scss colors.scss

ie8.scss:	sizes.scss colors.scss

#----------------------------------------------------------------------------
clean:
	rm -f connexions.css bookmarks.css users.css urlItems.css 	\
	      ui-form.css inbox.css validationForm.css post.css auth.css\
	      ie.css ie8.css

%.css : %.scss
	sass -t compact $<:$@

%.min.css : %.css
	$(YUI_COMP) $<
