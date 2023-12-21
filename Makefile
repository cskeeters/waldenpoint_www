ENGINE=docker
IMAGE=walden_point_app
APP_NAME=walden-point

# For running on a port
#NET_FLAGS=-p 8080:80
#NET_FLAGS=-p 127.0.0.1:8080:80

# In order to run behind nginx proxy manager (NPM), this container will net to be configured on the same docker network as NPM.
NETWORK=servers
IP=192.168.0.20
NET_FLAGS=--network $(NETWORK) --ip $(IP)

.PHONY: default logs debug bash stop check bake build

default:
	$(ENGINE) run -d $(NET_FLAGS) -v $(PWD)/app:/var/www/app -v $(PWD)/logs:/var/log/app --restart unless-stopped --name $(APP_NAME) $(IMAGE) serve

logs:
	$(ENGINE) logs $(APP_NAME)

debug:
	$(ENGINE) run -it --rm $(NET_FLAGS) -v $(PWD)/app:/var/www/app -v $(PWD)/logs:/var/log/app --name $(APP_NAME) $(IMAGE) serve

bash:
	$(ENGINE) run -it --rm -v $(PWD)/app:/var/www/app -v $(PWD)/logs:/var/log/app --name $(APP_NAME) $(IMAGE) bash

bashexec:
	$(ENGINE) exec -it $(APP_NAME) bash

stop:
	$(ENGINE) container rm -f $(APP_NAME)

check:
	$(ENGINE) exec -it $(APP_NAME) /init.sh check

bake:
	$(ENGINE) exec -it $(APP_NAME) /init.sh bake

build:
	$(ENGINE) build -t $(IMAGE) .
