#!/usr/bin/python3
#
#  Simple python script to traverse a web document root directory
#  Generate a whitelist configuration for the files
#  and directories
#
#  Ng Chiang Lin
#  Nov 2020
#  https://www.nighthour.sg/articles/2019/developing-nginx-url-whitelisting-module.html
# 

import os

rootdir = "HomePage" 
ignore_exts = ['jpg', 'png', 'svg','gif','webp']
directive = "wh_list_uri"


def checkFile(filename):

    #hidden file or directory
    if filename.startswith('.'):
        return False
    
    parts = filename.split('.')
    length = len(parts)
    
    #filename doesn't have a dot extension
    if length < 2 :
        return True
    
    #check that file extension is not in ignore list
    extension = parts[length - 1]
    for ext in ignore_exts:
        if extension == ext :
            return False

    return True

    
def formatRelativePath(path):

    parts = path.split('/')
    length = len(parts)
    
    if length < 2:
        print("An error occurred file path format is wrong")
        exit(1)
    
    relativepath = ""

    for i in range(1,length):
        if i < length -1 :
            relativepath = relativepath + parts[i] + "/"
        else:
            relativepath = relativepath + parts[i]
    
    return relativepath

    

def listDir(directory):

    with os.scandir(directory) as it:
        for entry in it:
            entryname = ''
            if entry.is_file() and checkFile(entry.name) :
                entryname = formatRelativePath(entry.path)
                print(directive, ' /', entryname, ' ;', sep='')
            elif entry.is_dir():
                entryname = formatRelativePath(entry.path)
                print(directive, ' /', entryname, '/ ;',sep='')
                listDir(entry.path)
    
    


if __name__ == "__main__":
    print(directive, ' / ;', sep='')
    listDir(rootdir)




