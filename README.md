Caveats
-------

- **Java stack size**. You might need to increase the Java stack size. When using the Neo4j
  docker container, increase it like this:
  
        docker run -d --name neo4j -e wrapper_java_additional=-Xss32m -e wrapper_java_maxmemory=4096 -p 7474:7474 tpires/neo4j