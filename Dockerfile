FROM slim_app:latest

RUN apk add --no-cache gcc python3 python3-dev musl-dev beancount

run pip install "bottle<0.13" --break-system-packages
run pip install legacy-cgi --break-system-packages

ADD init.sh /
RUN chmod 755 /init.sh
