crun() {
#    clear
    echo "Compiling $1"
    echo "Done"
    echo "Running $1.exe"
    g++ -o $1.exe $1 && ./$1.exe
}

weather() {
  curl wttr.in/$1
}

vpn() {
  sshuttle -r $1 -x 0.0.0.0 0/0 -v
}

control () {
  docker exec -i -t control-$1 /bin/bash
}


alias cli=./cli
alias tinker="cli exec php artisan tinker"
alias artisan="cli exec php artisan"
alias cmp="cli exec composer"
alias dc="docker-compose"
alias pls="sudo"
alias upgrade="sudo apt update && sudo apt full-upgrade -y && sudo apt autoremove -y"
alias envoy="./vendor/bin/envoy"

if [ -f ~/.bash_aliases_private ]; then
    . ~/.bash_aliases_private
fi
