import networkx as nx
from networkx.exception import NetworkXError
from networkx.utils import not_implemented_for

G = nx.read_edgelist("/Users/yun-tanghsu/Desktop/2020Fall/572/hw/hw4/edgelist.txt", create_using=nx.DiGraph())
pr = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight', dangling=None)

print("Writing...")
f = open("external_pageRankFile.txt", "w")
i=0
for x in pr:
	f.write("/Users/yun-tanghsu/Desktop/2020Fall/572/hw/hw4/NYTIMES/nytimes/")
	f.write(x)
	f.write("=")
	f.write(str(pr[x]))
	f.write("\n")
	print(i)
	i+=1

f.close()
print("done")
