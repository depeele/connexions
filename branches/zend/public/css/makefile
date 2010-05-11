YUI_COMP	= yuicompressor


all: connexions.min.css bookmarks.min.css users.min.css 	\
	urlItems.min.css inbox.min.css				\
 	validationForm.min.css post.min.css auth.min.css 	\
	inbox.min.css

#all: connexions.css bookmarks.css users.css urlItems.css inbox.css	\
# 	validationForm.css post.css auth.css inbox.css

connexions.css: main.sass		\
    		mixins.sass		\
		colors.sass		\
		sizes.sass		\
		content.sass		\
		grids.sass		\
		libraries.sass		\
		pagination.sass		\
		itemScope.sass		\
		sprites.sass		\
		tabs.sass		\
		tagCloud.sass		\
		template.sass		\
		ui-form.sass		\
		themes/default/jquery-ui.css
	sass -t compact main.sass connexions.css

bookmarks.css: mixins.sass bookmarks.sass bookmark.sass uiStars.sass \
		colors.sass sizes.sass
	sass -t compact bookmarks.sass bookmarks.css

users.css: mixins.sass users.sass colors.sass sizes.sass
	sass -t compact users.sass users.css

urlItems.css: mixins.sass urlItems.sass bookmark.sass uiStars.sass \
		colors.sass sizes.sass urls.sass
	sass -t compact urlItems.sass urlItems.css

inbox.css: mixins.sass inbox.sass colors.sass sizes.sass
	sass -t compact inbox.sass inbox.css

validationForm.css: mixins.sass validationForm.sass colors.sass uiStars.sass
	sass -t compact validationForm.sass validationForm.css

post.css: mixins.sass post.sass colors.sass
	sass -t compact post.sass post.css

auth.css: mixins.sass auth.sass colors.sass
	sass -t compact auth.sass auth.css

sprites.sass:	urls.sass

#----------------------------------------------------------------------------

%.min.css : %.css
	$(YUI_COMP) $<
