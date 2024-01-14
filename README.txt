# R3free

To start app for the first time 
1. open docker 
2. delete all containers 
3. $ docker compose up 
4. check mySQL workbench for 127.0.0.1:3306 
5. check web browser for localhost:8080 
6. great success!

7. while docker is running you cannnont git commands 
8. docker compose stop 
        to stop docker


        docker exec -i r3free-database-1 sh -c 'exec mysql -uroot -p"Moon1969"' < ./database/Migrations/Tester.sql


