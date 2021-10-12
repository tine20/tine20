sources := $(shell echo lib/*.sh)

lib.yml: $(sources)
	printf "# This file is generated!\n" > lib.yml
	printf "# source: ci/gitlab-ci/script\n" >> lib.yml
	printf "# build: ci/gitlab-ci && make lib.yml\n\n" >> lib.yml
	printf ".lib:\n  script:\n    - |\n" >> lib.yml;
	printf "      # ci/gitlab-ci/lib.yml:script\n" >> lib.yml;
	for f in $(sources); do  \
		 printf "      # $$f\n" >> lib.yml; \
		 sed -e 's/^/      /' $$f >> lib.yml; \
		 printf "\n\n" >> lib.yml; \
	done