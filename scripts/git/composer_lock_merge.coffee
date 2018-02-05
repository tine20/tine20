#!/usr/bin/env coffee

#################################################################
# credit goes to: jphass, because I totally ripped off his code #
#  (https://gist.github.com/jphaas/ad7823b3469aac112a52)        #
#################################################################

#################################################################################################
# INSTALLING                                                                                    #
#                                                                                               #
# * set this file executable                                                                    #
#                                                                                               #
# In your git configuration ($HOME/.gitconfig for user-wide), add:                              #
#                                                                                               #
# [merge "composer_lock_merge"]                                                                 #
#   name = A custom merge driver for json files                                                 #
#   driver = /PATH/TO/composer_lock_merge.coffee %O %A %B                                       #
#   recursive = binary                                                                          #
#                                                                                               #
# In your gitattributes ($HOME/.config/git/attributes for user-wide) file, add something like:  #
#                                                                                               #
# composer.lock merge=composer_lock_merge                                                       #
#################################################################################################

fs = require 'fs'

[ancestor, ours, theirs] = ( JSON.parse fs.readFileSync x for x in process.argv[2..4] )

# if this turns true, we'll exit non-zero so that git knows there are conflicts
conflicts = false

################################################################################
# Generate a node to indicate a conflict                                      #
# We include '<<<<<<<<>>>>>>>>' so that developers used to searching for <<<< #
# to find conflicts can maintain their current habits                         #
################################################################################
make_conflict_node = (ancestor_value, our_value, their_value) ->
  res = {}
  res['CONFLICT'] = '<<<<<<<>>>>>>>'
  res['OURS'] = our_value ? null
  res['THEIRS'] = their_value ? null
  res['ANCESTOR'] = ancestor_value ? null
  return res

merge = (ancestor_node, our_node, their_node) ->
  # collect a list of all of the keys
  keys = {}
  for key, _ of our_node
    keys[key] = true
  for key, _ of their_node
    keys[key] = true
  # except for 'packages' and 'packages-dev', we'll merge that elsewhere
  delete keys["packages"]
  delete keys["packages-dev"]

  for key, _i of keys
    ancestor_value = ancestor_node?[key]
    our_value = our_node?[key]
    their_value = their_node?[key]

    if our_value isnt their_value
      # if theirs is same as the ancestor, we'll stick with ours
      if JSON.stringify(their_value) is JSON.stringify(ancestor_value)
        continue

      # if ours is the same as the ancestor, then ours is older
      # we'll go with theirs
      else if JSON.stringify(our_value) is JSON.stringify(ancestor_value)
        our_node[key] = their_value

      # if our value and their value are both objects, then recursively merge
      else if our_value and their_value and typeof(our_value) is 'object' and typeof(their_value) is 'object'
        merge ancestor_value, our_value, their_value

      # if all else fails, then we can't auto-merge. we'll create a 'conflict node' to be manually merged
      else
        conflicts = true
        our_node[key] = make_conflict_node ancestor_value, our_value, their_value

add_package_node = (pkg_list, pkg, branch) ->
  pkg_list[pkg.name] = {} unless pkg_list[pkg.name]?
  pkg_list[pkg.name][branch] = pkg

package_merge = (ancestor_node, our_node, their_node) ->
  pkg_list = {}

  add_package_node(pkg_list, pkg, "ancestor") for pkg in ancestor_node
  add_package_node(pkg_list, pkg, "ours") for pkg in our_node
  add_package_node(pkg_list, pkg, "theirs") for pkg in their_node

  winners = []

  for k, pkg of pkg_list
    ancestor_version = pkg?["ancestor"]
    our_version = pkg?["ours"]
    their_version = pkg?["theirs"]

    # if theirs is same as the ancestor, we'll stick with ours
    if JSON.stringify(their_version) is JSON.stringify(ancestor_version)
      winners.push our_version

    # if ours is the same as the ancestor, then ours is older
    # we'll go with theirs
    else if JSON.stringify(our_version) is JSON.stringify(ancestor_version)
      winners.push their_version

    # if they're the same, just arbitrarily choose one
    else if JSON.stringify(our_version) is JSON.stringify(their_version)
      winners.push our_version

    # if all else fails, let's make a conflict node and let the user decide
    # it really doesn't do any good merging the innards since
    # dependencies and hashes vary from version to version
    else
      conflicts = true
      winners.push make_conflict_node ancestor_version, our_version, their_version

  # filter out the null and undefined elements from the list
  # those indicate that the package was removed from both branches
  # since splitting from the ancestor
  return winners.filter(Boolean)

merge ancestor, ours, theirs
ours.packages = package_merge ancestor.packages, ours.packages, theirs.packages
ours["packages-dev"] = package_merge ancestor["packages-dev"], ours["packages-dev"], theirs["packages-dev"]
ours.hash = "automerged"

fs.writeFileSync process.argv[3], (JSON.stringify ours, null, 4)

process.exit if conflicts then 1 else 0#!/usr/bin/env bash