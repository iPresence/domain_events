COMPONENT := domainevents
CONTAINER := phpfarm
IMAGES ?= false
PHP_VERSION ?: false
APP_ROOT := /app/domain-events

all: dev logs

dev:
	@docker-compose -p ${COMPONENT} -f ops/docker/docker-compose.yml up -d

enter:
	@docker exec -ti ${COMPONENT}_${CONTAINER}_1 /bin/bash

kill:
	@docker-compose -p ${COMPONENT} -f ops/docker/docker-compose.yml kill

nodev:
	@docker-compose -p ${COMPONENT} -f ops/docker/docker-compose.yml kill
	@docker-compose -p ${COMPONENT} -f ops/docker/docker-compose.yml rm -f
ifeq ($(IMAGES),true)
	@docker rmi ${COMPONENT}_${CONTAINER}
endif

test:
	make dev
	sleep 10
	make unit
	make integration

unit:
	@docker exec -t $(shell docker-compose -p ${COMPONENT} -f ops/docker/docker-compose.yml ps -q ${CONTAINER}) \
	 ${APP_ROOT}/ops/scripts/unit.sh ${PHP_VERSION}

integration:
	@docker exec -t $(shell docker-compose -p ${COMPONENT} -f ops/docker/docker-compose.yml ps -q ${CONTAINER}) \
	 ${APP_ROOT}/ops/scripts/integration.sh ${PHP_VERSION}

ps: status
status:
	@docker-compose -p ${COMPONENT} -f ops/docker/docker-compose.yml ps

logs:
	@docker-compose -p ${COMPONENT} -f ops/docker/docker-compose.yml logs

restart: nodev dev logs
