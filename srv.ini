#include <sys/socket.h>
#include <sys/un.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>

char *socket_path = "/tmp/bind-dlz-php-02.socket";
//char *socket_path = "\0hidden";

int main(int argc, char *argv[]) {
  struct sockaddr_un addr;
  char buff[2000];
  char message[2000];
  int fd,rc;

  if (argc > 1)
    strncpy(buff,argv[1],100);
  else
    strcpy(buff,"www.example.com");

  sprintf(message, "{\"messagetype\":\"dnsquery\",\"query\":{\"name\": \"%s\", \"type\":\"A\", \"class\":\"IN\"}}", buff);

  if ( (fd = socket(AF_UNIX, SOCK_STREAM, 0)) == -1) {
    perror("socket error");
    exit(-1);
  }

  memset(&addr, 0, sizeof(addr));
  addr.sun_family = AF_UNIX;
  if (*socket_path == '\0') {
    *addr.sun_path = '\0';
    strncpy(addr.sun_path+1, socket_path+1, sizeof(addr.sun_path)-2);
  } else {
    strncpy(addr.sun_path, socket_path, sizeof(addr.sun_path)-1);
  }

  if (connect(fd, (struct sockaddr*)&addr, sizeof(addr)) == -1) {
    perror("connect error");
    exit(-1);
  }
  printf("Sending message '%s'", message);
  while( write(fd, message, strlen(message)) == strlen(message) ) {
    if( (rc=read(fd, buff, sizeof(buff))) > 0) {
      printf("Message received: %s\n", buff);
    }
//    sleep(1);
  }

  return 0;
}
