#include <sys/socket.h>
#include <sys/un.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <signal.h>
#include <time.h>

char *socket_path = "/tmp/bind-dlz-php-02.socket";
//char *socket_path = "\0hidden";

int running=1;
int start=0;
int stop=0;

void signal_handler (int sg)
{
  running=0;
}

int main(int argc, char *argv[]) {
  struct sockaddr_un addr;
  char buff[2000];
  char message[2000];
  int fd,rc;
  int queries=0;
  int max=1000;

  signal(SIGINT, signal_handler);

  if (argc > 1)
    strncpy(buff,argv[1],100);
  else if (argc > 2)
    sscanf(argv[2],"%d",&max);
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
  start=(unsigned long)time(NULL);
  while( running == 1 && queries < max && write(fd, message, strlen(message)) == strlen(message) ) {
    if( (rc=read(fd, buff, sizeof(buff))) > 0) {
      printf(".");
      queries++;
    } else {
      printf("e");
    }
    fflush(stdout);

//    sleep(1);
  }
  stop=(unsigned long)time(NULL);
  printf("\nQueries processed: %d in %d sec, %.2f r/s\n", queries, stop-start, (float)queries/(stop-start));

  return 0;
}
