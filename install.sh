#!/bin/bash
clear;
sudo apt install git php;
sudo systemctl enable nftables.service;
sudo systemctl start nftables.service;
while true
do
    echo -e 'Which Server to Install?\n 0. Frontend\n 1. DMZ\n 2. Middleware\n 3. Database';
    read server_selection;
    case "$server_selection" in
      0)
        echo "love";
        clear
      ;;
      1)
        echo "love";
        clear
      ;;
      2)
        echo "love";
        clear
      ;;
      3)
        echo "love";
        clear
      ;;
      *)
        clear;
        echo "Choose between 0-3.";
      ;;
      esac

done
