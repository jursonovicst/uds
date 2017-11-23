#include <sys/socket.h>
#include <sys/un.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>

char *socket_path = "/tmp/srv.socket";
//char *socket_path = "\0hidden";

int main(int argc, char *argv[]) {
  struct sockaddr_un addr;
  char buf[100];
  char message[100];
  int fd,rc;

  if (argc > 1) strncpy(message,argv[1],100);
  else strcpy(message,"n/a");

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

  while( write(fd, message, sizeof(message)) == sizeof(message) ) {
    if( (rc=read(fd, buf, sizeof(buf))) > 0) {
      printf("Message received: %s\n", buf);
    }
    sleep(10);
  }
  //while( (rc=read(STDIN_FILENO, buf, sizeof(buf))) > 0) {
  //  if (write(fd, buf, rc) != rc) {
  //    if (rc > 0) fprintf(stderr,"partial write");
  //    else {
  //      perror("write error");
  //      exit(-1);
  //    }
  //  }
  //}

  return 0;
}
