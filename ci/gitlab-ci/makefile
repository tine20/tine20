scripts_sources := $(shell echo lib/scripts/*.sh)
entry_points_sources := $(shell echo lib/entry_points/*.sh)

lib.yml: $(scripts_sources) $(entry_points_sources) makefile
	printf "# This file is generated!\n" > lib.yml
	printf "# source: ci/gitlab-ci/script\n" >> lib.yml
	printf "# build: ci/gitlab-ci && make lib.yml\n\n" >> lib.yml
	printf ".lib:\n" >> lib.yml;
	printf "  script:\n" >> lib.yml;
	printf "    - |\n" >> lib.yml;
	for f in $(scripts_sources); do  \
		printf "      # $$f\n" >> lib.yml; \
		sed -e 's/^/      /' $$f >> lib.yml; \
		printf "\n\n" >> lib.yml; \
	done
	printf "  entry_points:\n" >> lib.yml;
	for f in $(entry_points_sources); do  \
		printf "    $$(basename $$f .sh):\n" >> lib.yml; \
		printf "      - bash\n" >> lib.yml; \
		printf "      - -c\n" >> lib.yml; \
		printf "      - |\n" >> lib.yml; \
		printf "        # $$f\n" >> lib.yml; \
		sed -e 's/^/        /' $$f >> lib.yml; \
		printf "\n\n" >> lib.yml; \
	done