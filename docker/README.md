# Setting up docker container with mysql database for testing purposes

Dependencies:
 * docker
 * docker-compose

Pull mysql image container
```bash
docker pull mysql
```

Create and start container(s)
```bash
docker-compose up
```

### Cleanup
Remove existing container(s)
```bash
docker-compose down --rmi all 
```
