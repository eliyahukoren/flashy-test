build:
	docker build -t flashy-test .
run:
	docker run -it --rm --name flashy-test-app flashy-test
start:
	docker-compose up --build app	
