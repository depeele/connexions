all:
	@./updateBuildNumber.sh
	@cd public/js; make
	@cd public/css; make

clean:
	@cd public/js; make clean
	@cd public/css; make clean

tags:	FORCE
	@ctags -R application library

FORCE:
